<?php
declare(strict_types=1);

namespace BTL\Services;

use BTL\Database;
use PDO;
use Exception;
use RuntimeException;

class WarehouseService {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Lấy danh sách kho mà người dùng được phép truy cập
     */
    public function getWarehousesForUser(int $userId): array {
        // Kiểm tra xem user có phải admin không
        $stmt = $this->db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if ($user && $user['role'] === 'admin') {
            return $this->db->query("SELECT * FROM warehouses WHERE is_active = 1 ORDER BY id ASC")->fetchAll();
        }

        $stmt = $this->db->prepare("
            SELECT w.* 
            FROM warehouses w
            JOIN user_warehouses uw ON w.id = uw.warehouse_id
            WHERE uw.user_id = ? AND w.is_active = 1
            ORDER BY w.id ASC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Kiểm tra quyền của người dùng với một kho cụ thể (Server-side check)
     */
    public function canAccessWarehouse(int $userId, int $warehouseId): bool {
        $stmt = $this->db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) return false;
        if ($user['role'] === 'admin') return true;

        $stmt = $this->db->prepare("SELECT 1 FROM user_warehouses WHERE user_id = ? AND warehouse_id = ?");
        $stmt->execute([$userId, $warehouseId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Lấy số lượng tồn kho tức thời của một mặt hàng tại kho
     */
    public function getStock(int $warehouseId, int $itemId, ?int $batchId = null): float {
        if ($batchId !== null) {
            $stmt = $this->db->prepare("
                SELECT quantity FROM inventory 
                WHERE warehouse_id = ? AND item_id = ? AND batch_id = ?
            ");
            $stmt->execute([$warehouseId, $itemId, $batchId]);
        } else {
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(quantity), 0) FROM inventory 
                WHERE warehouse_id = ? AND item_id = ?
            ");
            $stmt->execute([$warehouseId, $itemId]);
        }
        $val = $stmt->fetchColumn();
        return $val !== false ? (float)$val : 0.0;
    }

    /**
     * Xác nhận phiếu nhập kho (IMPORT)
     * Kỹ thuật: Idempotency + Immutable Ledger + Inventory update
     */
    public function confirmImportVoucher(int $voucherId, int $confirmedBy): bool {
        $this->db->beginTransaction();
        try {
            // Khóa dòng voucher để tránh race condition (FOR UPDATE)
            $stmt = $this->db->prepare("SELECT * FROM vouchers WHERE id = ? FOR UPDATE");
            $stmt->execute([$voucherId]);
            $voucher = $stmt->fetch();

            if (!$voucher) {
                throw new RuntimeException("Chứng từ không tồn tại.");
            }
            if ($voucher['type'] !== 'IMPORT') {
                throw new RuntimeException("Không phải chứng từ nhập kho.");
            }
            if ($voucher['status'] === 'CONFIRMED') {
                // Idempotency: Không ghi nhận thêm lần nữa nếu đã xác nhận
                $this->db->rollBack();
                return true;
            }
            if ($voucher['status'] === 'CANCELLED') {
                throw new RuntimeException("Chứng từ này đã bị hủy.");
            }

            // Kiểm tra quyền kho nhập của người xác nhận
            if (!$this->canAccessWarehouse($confirmedBy, (int)$voucher['target_warehouse_id'])) {
                throw new RuntimeException("Bạn không có quyền xác nhận nhập cho kho này.");
            }

            $warehouseId = (int)$voucher['target_warehouse_id'];

            // Lấy danh sách dòng hàng
            $stmt = $this->db->prepare("SELECT * FROM voucher_lines WHERE voucher_id = ?");
            $stmt->execute([$voucherId]);
            $lines = $stmt->fetchAll();

            if (empty($lines)) {
                throw new RuntimeException("Phiếu nhập không có dòng hàng nào.");
            }

            foreach ($lines as $line) {
                $itemId = (int)$line['item_id'];
                $batchId = $line['batch_id'] ? (int)$line['batch_id'] : null;
                $qty = (float)$line['quantity'];

                if ($qty <= 0) {
                    throw new RuntimeException("Số lượng nhập phải lớn hơn 0.");
                }

                // Cập nhật tồn kho (Upsert inventory với khóa FOR UPDATE)
                $invStmt = $this->db->prepare("
                    SELECT quantity FROM inventory 
                    WHERE warehouse_id = ? AND item_id = ? AND " . ($batchId ? "batch_id = ?" : "batch_id IS NULL") . " 
                    FOR UPDATE
                ");
                $invParams = $batchId ? [$warehouseId, $itemId, $batchId] : [$warehouseId, $itemId];
                $invStmt->execute($invParams);
                $currentQty = $invStmt->fetchColumn();

                if ($currentQty === false) {
                    $newQty = $qty;
                    $insertInv = $this->db->prepare("
                        INSERT INTO inventory (warehouse_id, item_id, batch_id, quantity) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $insertInv->execute([$warehouseId, $itemId, $batchId, $newQty]);
                } else {
                    $newQty = (float)$currentQty + $qty;
                    $updateInv = $this->db->prepare("
                        UPDATE inventory SET quantity = ? 
                        WHERE warehouse_id = ? AND item_id = ? AND " . ($batchId ? "batch_id = ?" : "batch_id IS NULL") . "
                    ");
                    $updateParams = $batchId ? [$newQty, $warehouseId, $itemId, $batchId] : [$newQty, $warehouseId, $itemId];
                    $updateInv->execute($updateParams);
                }

                // Ghi vào Sổ phát sinh bất biến (Stock Movements Ledger)
                $ledgerStmt = $this->db->prepare("
                    INSERT INTO stock_movements (voucher_id, warehouse_id, item_id, batch_id, quantity, balance_after, movement_type)
                    VALUES (?, ?, ?, ?, ?, ?, 'IMPORT')
                ");
                $ledgerStmt->execute([$voucherId, $warehouseId, $itemId, $batchId, $qty, $newQty]);
            }

            // Đổi trạng thái phiếu sang CONFIRMED
            $updateVoucher = $this->db->prepare("
                UPDATE vouchers 
                SET status = 'CONFIRMED', confirmed_by = ?, confirmed_at = NOW() 
                WHERE id = ?
            ");
            $updateVoucher->execute([$confirmedBy, $voucherId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Xác nhận phiếu xuất kho (EXPORT)
     * Kỹ thuật 1: Kiểm tra khóa dòng, chặn xuất âm (Negative balance prevention),
     * Sổ phát sinh ghi nhận đầy đủ, Idempotent.
     */
    public function confirmExportVoucher(int $voucherId, int $confirmedBy): bool {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM vouchers WHERE id = ? FOR UPDATE");
            $stmt->execute([$voucherId]);
            $voucher = $stmt->fetch();

            if (!$voucher) {
                throw new RuntimeException("Chứng từ không tồn tại.");
            }
            if ($voucher['type'] !== 'EXPORT') {
                throw new RuntimeException("Không phải chứng từ xuất kho.");
            }
            if ($voucher['status'] === 'CONFIRMED') {
                $this->db->rollBack();
                return true;
            }
            if ($voucher['status'] === 'CANCELLED') {
                throw new RuntimeException("Chứng từ này đã bị hủy.");
            }

            // Kiểm tra quyền xuất kho phía server
            if (!$this->canAccessWarehouse($confirmedBy, (int)$voucher['source_warehouse_id'])) {
                throw new RuntimeException("Bạn không có quyền thao tác trên kho xuất này.");
            }

            $warehouseId = (int)$voucher['source_warehouse_id'];

            $stmt = $this->db->prepare("SELECT * FROM voucher_lines WHERE voucher_id = ?");
            $stmt->execute([$voucherId]);
            $lines = $stmt->fetchAll();

            if (empty($lines)) {
                throw new RuntimeException("Phiếu xuất không có dòng hàng nào.");
            }

            foreach ($lines as $line) {
                $itemId = (int)$line['item_id'];
                $batchId = $line['batch_id'] ? (int)$line['batch_id'] : null;
                $qty = (float)$line['quantity'];

                if ($qty <= 0) {
                    throw new RuntimeException("Số lượng xuất phải lớn hơn 0.");
                }

                // Khóa tồn kho để kiểm tra
                $invStmt = $this->db->prepare("
                    SELECT quantity FROM inventory 
                    WHERE warehouse_id = ? AND item_id = ? AND " . ($batchId ? "batch_id = ?" : "batch_id IS NULL") . " 
                    FOR UPDATE
                ");
                $invParams = $batchId ? [$warehouseId, $itemId, $batchId] : [$warehouseId, $itemId];
                $invStmt->execute($invParams);
                $currentQty = $invStmt->fetchColumn();

                if ($currentQty === false || (float)$currentQty < $qty) {
                    $available = ($currentQty === false) ? 0 : (float)$currentQty;
                    throw new RuntimeException("Không đủ tồn kho để xuất. Tồn hiện tại: {$available}, yêu cầu xuất: {$qty}");
                }

                $newQty = (float)$currentQty - $qty;

                // Cập nhật tồn kho
                $updateInv = $this->db->prepare("
                    UPDATE inventory SET quantity = ? 
                    WHERE warehouse_id = ? AND item_id = ? AND " . ($batchId ? "batch_id = ?" : "batch_id IS NULL") . "
                ");
                $updateParams = $batchId ? [$newQty, $warehouseId, $itemId, $batchId] : [$newQty, $warehouseId, $itemId];
                $updateInv->execute($updateParams);

                // Ghi nhận vào Sổ phát sinh: Số lượng âm phản ánh phát sinh giảm
                $ledgerStmt = $this->db->prepare("
                    INSERT INTO stock_movements (voucher_id, warehouse_id, item_id, batch_id, quantity, balance_after, movement_type)
                    VALUES (?, ?, ?, ?, ?, ?, 'EXPORT')
                ");
                $ledgerStmt->execute([$voucherId, $warehouseId, $itemId, $batchId, -$qty, $newQty]);
            }

            $updateVoucher = $this->db->prepare("
                UPDATE vouchers 
                SET status = 'CONFIRMED', confirmed_by = ?, confirmed_at = NOW() 
                WHERE id = ?
            ");
            $updateVoucher->execute([$confirmedBy, $voucherId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Xác nhận phiếu điều chuyển (TRANSFER)
     * Kỹ thuật 2 (Bắt buộc trong Rubric):
     * Điều chuyển phải trừ kho đi và cộng kho đến trong cùng một giao dịch (ACID).
     * Lỗi giữa hai thao tác không được làm mất hoặc nhân đôi hàng hóa.
     * $simulateCrashPoint: dùng để kiểm thử tự động khả năng rollback khi xảy ra lỗi giữa chừng.
     */
    public function confirmTransferVoucher(int $voucherId, int $confirmedBy, bool $simulateCrashPoint = false): bool {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM vouchers WHERE id = ? FOR UPDATE");
            $stmt->execute([$voucherId]);
            $voucher = $stmt->fetch();

            if (!$voucher) {
                throw new RuntimeException("Chứng từ không tồn tại.");
            }
            if ($voucher['type'] !== 'TRANSFER') {
                throw new RuntimeException("Không phải chứng từ điều chuyển.");
            }
            if ($voucher['status'] === 'CONFIRMED') {
                $this->db->rollBack();
                return true;
            }
            if ($voucher['status'] === 'CANCELLED') {
                throw new RuntimeException("Chứng từ này đã bị hủy.");
            }

            $sourceWhId = (int)$voucher['source_warehouse_id'];
            $targetWhId = (int)$voucher['target_warehouse_id'];

            if ($sourceWhId === $targetWhId) {
                throw new RuntimeException("Kho xuất và kho nhận điều chuyển không được trùng nhau.");
            }

            // Quyền thao tác kho xuất
            if (!$this->canAccessWarehouse($confirmedBy, $sourceWhId)) {
                throw new RuntimeException("Bạn không có quyền thao tác xuất tại kho nguồn.");
            }

            $stmt = $this->db->prepare("SELECT * FROM voucher_lines WHERE voucher_id = ?");
            $stmt->execute([$voucherId]);
            $lines = $stmt->fetchAll();

            if (empty($lines)) {
                throw new RuntimeException("Phiếu điều chuyển không có dòng hàng nào.");
            }

            foreach ($lines as $line) {
                $itemId = (int)$line['item_id'];
                $batchId = $line['batch_id'] ? (int)$line['batch_id'] : null;
                $qty = (float)$line['quantity'];

                if ($qty <= 0) {
                    throw new RuntimeException("Số lượng điều chuyển phải lớn hơn 0.");
                }

                // 1. TRỪ KHO ĐI (Source Warehouse)
                $srcStmt = $this->db->prepare("
                    SELECT quantity FROM inventory 
                    WHERE warehouse_id = ? AND item_id = ? AND " . ($batchId ? "batch_id = ?" : "batch_id IS NULL") . " 
                    FOR UPDATE
                ");
                $srcParams = $batchId ? [$sourceWhId, $itemId, $batchId] : [$sourceWhId, $itemId];
                $srcStmt->execute($srcParams);
                $srcCurrent = $srcStmt->fetchColumn();

                if ($srcCurrent === false || (float)$srcCurrent < $qty) {
                    $av = ($srcCurrent === false) ? 0 : (float)$srcCurrent;
                    throw new RuntimeException("Kho nguồn không đủ hàng để điều chuyển. Hiện có: {$av}, cần chuyển: {$qty}");
                }

                $srcNew = (float)$srcCurrent - $qty;
                $updateSrc = $this->db->prepare("
                    UPDATE inventory SET quantity = ? 
                    WHERE warehouse_id = ? AND item_id = ? AND " . ($batchId ? "batch_id = ?" : "batch_id IS NULL") . "
                ");
                $updateSrcParams = $batchId ? [$srcNew, $sourceWhId, $itemId, $batchId] : [$srcNew, $sourceWhId, $itemId];
                $updateSrc->execute($updateSrcParams);

                // Ghi sổ phát sinh cho kho nguồn
                $ledgerSrc = $this->db->prepare("
                    INSERT INTO stock_movements (voucher_id, warehouse_id, item_id, batch_id, quantity, balance_after, movement_type)
                    VALUES (?, ?, ?, ?, ?, ?, 'TRANSFER_OUT')
                ");
                $ledgerSrc->execute([$voucherId, $sourceWhId, $itemId, $batchId, -$qty, $srcNew]);

                // ĐIỂM MÔ PHỎNG SỰ CỐ (Simulate crash giữa 2 bước trừ kho đi và cộng kho đến)
                if ($simulateCrashPoint) {
                    throw new RuntimeException("SIMULATED_CRASH: Mất kết nối hoặc lỗi hệ thống giữa lúc trừ kho nguồn và cộng kho đích.");
                }

                // 2. CỘNG KHO ĐẾN (Target Warehouse) trong cùng transaction
                $tgtStmt = $this->db->prepare("
                    SELECT quantity FROM inventory 
                    WHERE warehouse_id = ? AND item_id = ? AND " . ($batchId ? "batch_id = ?" : "batch_id IS NULL") . " 
                    FOR UPDATE
                ");
                $tgtParams = $batchId ? [$targetWhId, $itemId, $batchId] : [$targetWhId, $itemId];
                $tgtStmt->execute($tgtParams);
                $tgtCurrent = $tgtStmt->fetchColumn();

                if ($tgtCurrent === false) {
                    $tgtNew = $qty;
                    $insertTgt = $this->db->prepare("
                        INSERT INTO inventory (warehouse_id, item_id, batch_id, quantity) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $insertTgt->execute([$targetWhId, $itemId, $batchId, $tgtNew]);
                } else {
                    $tgtNew = (float)$tgtCurrent + $qty;
                    $updateTgt = $this->db->prepare("
                        UPDATE inventory SET quantity = ? 
                        WHERE warehouse_id = ? AND item_id = ? AND " . ($batchId ? "batch_id = ?" : "batch_id IS NULL") . "
                    ");
                    $updateTgtParams = $batchId ? [$tgtNew, $targetWhId, $itemId, $batchId] : [$tgtNew, $targetWhId, $itemId];
                    $updateTgt->execute($updateTgtParams);
                }

                // Ghi sổ phát sinh cho kho đích
                $ledgerTgt = $this->db->prepare("
                    INSERT INTO stock_movements (voucher_id, warehouse_id, item_id, batch_id, quantity, balance_after, movement_type)
                    VALUES (?, ?, ?, ?, ?, ?, 'TRANSFER_IN')
                ");
                $ledgerTgt->execute([$voucherId, $targetWhId, $itemId, $batchId, $qty, $tgtNew]);
            }

            $updateVoucher = $this->db->prepare("
                UPDATE vouchers 
                SET status = 'CONFIRMED', confirmed_by = ?, confirmed_at = NOW() 
                WHERE id = ?
            ");
            $updateVoucher->execute([$confirmedBy, $voucherId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Xác nhận đợt kiểm kê & Tạo chứng từ điều chỉnh (ADJUSTMENT)
     * Chức năng 4: Tính chênh lệch và tạo phiếu điều chỉnh có lý do
     */
    public function confirmStockCount(int $stockCountId, int $confirmedBy): int {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM stock_counts WHERE id = ? FOR UPDATE");
            $stmt->execute([$stockCountId]);
            $count = $stmt->fetch();

            if (!$count) {
                throw new RuntimeException("Đợt kiểm kê không tồn tại.");
            }
            if ($count['status'] === 'CONFIRMED') {
                $this->db->rollBack();
                return (int)$count['adjustment_voucher_id'];
            }

            $warehouseId = (int)$count['warehouse_id'];

            // Lấy các dòng kiểm kê có chênh lệch
            $stmt = $this->db->prepare("SELECT * FROM stock_count_lines WHERE stock_count_id = ?");
            $stmt->execute([$stockCountId]);
            $lines = $stmt->fetchAll();

            $diffLines = array_filter($lines, fn($l) => (float)$l['difference'] != 0);

            $adjVoucherId = 0;
            if (!empty($diffLines)) {
                // Tạo phiếu điều chỉnh kho ADJUSTMENT
                $adjCode = 'ADJ-' . date('Ymd') . '-' . substr(uniqid(), -4);
                $vStmt = $this->db->prepare("
                    INSERT INTO vouchers (code, type, target_warehouse_id, created_by, confirmed_by, status, note, confirmed_at)
                    VALUES (?, 'ADJUSTMENT', ?, ?, ?, 'CONFIRMED', ?, NOW())
                ");
                $vNote = "Điều chỉnh tự động từ đợt kiểm kê " . $count['code'];
                $vStmt->execute([$adjCode, $warehouseId, $confirmedBy, $confirmedBy, $vNote]);
                $adjVoucherId = (int)$this->db->lastInsertId();

                foreach ($diffLines as $line) {
                    $itemId = (int)$line['item_id'];
                    $batchId = $line['batch_id'] ? (int)$line['batch_id'] : null;
                    $diff = (float)$line['difference']; // dương = thừa, âm = thiếu
                    $reason = $line['reason'] ?? 'Điều chỉnh kiểm kê';

                    // Ghi dòng chi tiết phiếu điều chỉnh
                    $vlStmt = $this->db->prepare("
                        INSERT INTO voucher_lines (voucher_id, item_id, batch_id, quantity, note)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $vlStmt->execute([$adjVoucherId, $itemId, $batchId, $diff, $reason]);

                    // Cập nhật tồn kho theo số thực tế
                    $invStmt = $this->db->prepare("
                        SELECT quantity FROM inventory 
                        WHERE warehouse_id = ? AND item_id = ? AND " . ($batchId ? "batch_id = ?" : "batch_id IS NULL") . " 
                        FOR UPDATE
                    ");
                    $invParams = $batchId ? [$warehouseId, $itemId, $batchId] : [$warehouseId, $itemId];
                    $invStmt->execute($invParams);
                    $cur = $invStmt->fetchColumn();

                    $newQty = (float)$line['actual_quantity'];

                    if ($cur === false) {
                        $ins = $this->db->prepare("
                            INSERT INTO inventory (warehouse_id, item_id, batch_id, quantity) 
                            VALUES (?, ?, ?, ?)
                        ");
                        $ins->execute([$warehouseId, $itemId, $batchId, $newQty]);
                    } else {
                        $upd = $this->db->prepare("
                            UPDATE inventory SET quantity = ? 
                            WHERE warehouse_id = ? AND item_id = ? AND " . ($batchId ? "batch_id = ?" : "batch_id IS NULL") . "
                        ");
                        $updParams = $batchId ? [$newQty, $warehouseId, $itemId, $batchId] : [$newQty, $warehouseId, $itemId];
                        $upd->execute($updParams);
                    }

                    // Ghi Sổ phát sinh
                    $ledger = $this->db->prepare("
                        INSERT INTO stock_movements (voucher_id, warehouse_id, item_id, batch_id, quantity, balance_after, movement_type)
                        VALUES (?, ?, ?, ?, ?, ?, 'ADJUSTMENT')
                    ");
                    $ledger->execute([$adjVoucherId, $warehouseId, $itemId, $batchId, $diff, $newQty]);
                }
            }

            // Đổi trạng thái đợt kiểm kê
            $updCount = $this->db->prepare("
                UPDATE stock_counts 
                SET status = 'CONFIRMED', confirmed_at = NOW(), adjustment_voucher_id = ? 
                WHERE id = ?
            ");
            $updCount->execute([$adjVoucherId ?: null, $stockCountId]);

            $this->db->commit();
            return $adjVoucherId;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
