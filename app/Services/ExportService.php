<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Refund;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    /**
     * Generate streamed CSV response for requested module.
     */
    public function exportCsv(string $module, array $range, array $filters = []): StreamedResponse
    {
        $filename = "export_{$module}_" . date('Y-m-d_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($module, $range, $filters) {
            $handle = fopen('php://output', 'w');

            switch ($module) {
                case 'orders':
                    $this->streamOrders($handle, $range, $filters);
                    break;

                case 'products':
                    $this->streamProducts($handle, $filters);
                    break;

                case 'customers':
                    $this->streamCustomers($handle, $range, $filters);
                    break;

                case 'inventory':
                    $this->streamInventory($handle, $filters);
                    break;

                case 'payments':
                    $this->streamPayments($handle, $range);
                    break;

                case 'refunds':
                    $this->streamRefunds($handle, $range);
                    break;

                case 'returns':
                    $this->streamReturns($handle, $range);
                    break;

                case 'profit':
                    $this->streamProfit($handle, $range);
                    break;

                case 'gst':
                    $this->streamGst($handle, $range);
                    break;

                default:
                    fputcsv($handle, ['Error', 'Unsupported export module']);
                    break;
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function streamOrders($handle, array $range, array $filters): void
    {
        fputcsv($handle, ['ID', 'Order Number', 'WooCommerce ID', 'Customer Name', 'Email', 'Phone', 'Total', 'Status', 'Payment Status', 'Payment Method', 'Created At']);

        $query = Order::with('user')
            ->whereBetween('created_at', [$range['start'], $range['end']]);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $query->chunk(100, function ($orders) use ($handle) {
            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->id,
                    $order->order_num,
                    $order->woocommerce_id ?? 'N/A',
                    $order->user?->name ?? 'Guest',
                    $order->user?->email ?? 'N/A',
                    $order->user?->phone ?? 'N/A',
                    $order->total,
                    $order->status,
                    $order->pay_status,
                    $order->payment_method ?? 'N/A',
                    $order->created_at->toDateTimeString(),
                ]);
            }
        });
    }

    private function streamProducts($handle, array $filters): void
    {
        fputcsv($handle, ['ID', 'SKU', 'Name', 'Category', 'Brand', 'Price', 'Sale Price', 'Stock Qty', 'Status', 'Featured']);

        $query = Product::with(['category', 'brand']);

        if (! empty($filters['category'])) {
            $query->where('category_id', $filters['category']);
        }

        $query->chunk(100, function ($products) use ($handle) {
            foreach ($products as $p) {
                fputcsv($handle, [
                    $p->id,
                    $p->sku,
                    $p->name,
                    $p->category?->name ?? 'N/A',
                    $p->brand?->name ?? 'N/A',
                    $p->price,
                    $p->sale_price ?? 'N/A',
                    $p->stock_qty,
                    $p->status,
                    $p->featured ? 'Yes' : 'No',
                ]);
            }
        });
    }

    private function streamCustomers($handle, array $range, array $filters): void
    {
        // STRICT SECURITY: Never export password hashes or secret tokens
        fputcsv($handle, ['ID', 'Name', 'Email', 'Phone', 'Role', 'Status', 'Registered At']);

        $query = User::with('role')
            ->whereHas('role', fn ($rq) => $rq->where('name', 'Customer'))
            ->whereBetween('created_at', [$range['start'], $range['end']]);

        $query->chunk(100, function ($users) use ($handle) {
            foreach ($users as $u) {
                fputcsv($handle, [
                    $u->id,
                    $u->name,
                    $u->email,
                    $u->phone ?? 'N/A',
                    $u->role?->name ?? 'Customer',
                    $u->status,
                    $u->created_at->toDateTimeString(),
                ]);
            }
        });
    }

    private function streamInventory($handle, array $filters): void
    {
        fputcsv($handle, ['Product ID', 'SKU', 'Product Name', 'Current Stock', 'Reserved Stock', 'Available Stock', 'Low Stock Threshold']);

        $query = Product::query();

        $query->chunk(100, function ($products) use ($handle) {
            foreach ($products as $p) {
                fputcsv($handle, [
                    $p->id,
                    $p->sku,
                    $p->name,
                    $p->stock_qty,
                    $p->reserved_stock ?? 0,
                    $p->available_stock,
                    $p->low_stock_qty ?? 5,
                ]);
            }
        });
    }

    private function streamPayments($handle, array $range): void
    {
        fputcsv($handle, ['ID', 'Order Number', 'Amount', 'Payment Method', 'Status', 'Transaction ID', 'Date']);

        $query = Payment::with('order')
            ->whereBetween('created_at', [$range['start'], $range['end']]);

        $query->chunk(100, function ($payments) use ($handle) {
            foreach ($payments as $pay) {
                fputcsv($handle, [
                    $pay->id,
                    $pay->order?->order_num ?? 'N/A',
                    $pay->amount,
                    $pay->payment_method ?? $pay->method ?? 'N/A',
                    $pay->status,
                    $pay->txn_id ?? 'N/A',
                    $pay->created_at->toDateTimeString(),
                ]);
            }
        });
    }

    private function streamRefunds($handle, array $range): void
    {
        fputcsv($handle, ['ID', 'Order ID', 'Amount', 'Reason', 'Status', 'Created At']);

        $query = Refund::whereBetween('created_at', [$range['start'], $range['end']]);

        $query->chunk(100, function ($refunds) use ($handle) {
            foreach ($refunds as $ref) {
                fputcsv($handle, [
                    $ref->id,
                    $ref->order_id,
                    $ref->amount,
                    $ref->reason ?? 'N/A',
                    $ref->status ?? 'processed',
                    $ref->created_at->toDateTimeString(),
                ]);
            }
        });
    }

    private function streamReturns($handle, array $range): void
    {
        fputcsv($handle, ['ID', 'Order ID', 'Return Code', 'Reason', 'Status', 'Created At']);

        $query = OrderReturn::whereBetween('created_at', [$range['start'], $range['end']]);

        $query->chunk(100, function ($returns) use ($handle) {
            foreach ($returns as $ret) {
                fputcsv($handle, [
                    $ret->id,
                    $ret->order_id,
                    $ret->return_code ?? $ret->id,
                    $ret->reason ?? 'N/A',
                    $ret->status,
                    $ret->created_at->toDateTimeString(),
                ]);
            }
        });
    }

    private function streamProfit($handle, array $range): void
    {
        fputcsv($handle, ['Product ID', 'SKU', 'Product Name', 'Units Sold', 'Revenue', 'Cost', 'Gross Profit', 'Margin %']);

        $reportService = new ReportService();
        $profitData = $reportService->getProfitReport($range);

        foreach ($profitData['product_breakdown'] as $row) {
            fputcsv($handle, [
                $row['id'],
                $row['sku'],
                $row['name'],
                $row['units_sold'],
                $row['revenue'],
                $row['cost'],
                $row['gross_profit'],
                $row['margin_pct'] . '%',
            ]);
        }
    }

    private function streamGst($handle, array $range): void
    {
        fputcsv($handle, ['HSN Code', 'Total Quantity', 'Total Revenue']);

        $reportService = new ReportService();
        $gstData = $reportService->getGstReport($range);

        foreach ($gstData['hsn_summary'] as $row) {
            fputcsv($handle, [
                $row['hsn_code'],
                $row['total_qty'],
                $row['revenue'],
            ]);
        }
    }
}
