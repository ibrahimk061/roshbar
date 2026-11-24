<?php

namespace App\Http\Controllers;

use App\Services\SalesReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function sales(Request $request, SalesReportService $reports): View
    {
        [$from, $to, $preset] = $this->range($request);

        return view('reports.sales', [
            'r' => $reports->build($from, $to),
            'preset' => $preset,
            'presets' => SalesReportService::presets(),
        ]);
    }

    /** Item-wise sales as a CSV file (opens in Excel). */
    public function salesCsv(Request $request, SalesReportService $reports): StreamedResponse
    {
        [$from, $to] = $this->range($request);
        $r = $reports->build($from, $to);
        $name = 'roshbar-sales-'.$from->format('Y-m-d').($from->eq($to) ? '' : '-to-'.$to->format('Y-m-d')).'.csv';

        return response()->streamDownload(function () use ($r) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows ৳ and Bangla names
            fputcsv($out, ['Item', 'Size', 'Qty', 'Amount (BDT)']);
            foreach ($r['items'] as $i) {
                fputcsv($out, [$i['product'], $i['size'], $i['qty'], $i['amount']]);
            }
            foreach ($r['addons'] as $a) {
                fputcsv($out, ['Add-on: '.$a['name'], '', $a['qty'], $a['amount']]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Orders', $r['orders']]);
            fputcsv($out, ['Subtotal', $r['subtotal']]);
            fputcsv($out, ['Discount', $r['discount']]);
            fputcsv($out, ['VAT', $r['tax']]);
            fputcsv($out, ['Net sales', $r['total']]);
            foreach ($r['payments'] as $p) {
                fputcsv($out, ['Paid by '.$p['method'], $p['amount']]);
            }
            fputcsv($out, []);
            foreach ($r['expenses'] as $e) {
                fputcsv($out, ['Expense: '.$e['name'], $e['amount']]);
            }
            fputcsv($out, ['Total expenses', $r['expense_total']]);
            fputcsv($out, ['Sales minus expenses', $r['net']]);
            fputcsv($out, []);
            fputcsv($out, ['Date', 'Orders', 'Sales', 'Expenses', 'Sales minus expenses']);
            foreach ($r['days'] as $d) {
                fputcsv($out, [$d['date'], $d['orders'], $d['total'], $d['expense'], $d['net']]);
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: ?string} */
    private function range(Request $request): array
    {
        $presets = SalesReportService::presets();
        $preset = $request->query('range');
        if (! $request->filled('from') && ! isset($presets[$preset])) {
            $preset = 'today';
        }
        if (isset($presets[$preset]) && ! $request->filled('from')) {
            return [$presets[$preset][1], $presets[$preset][2], $preset];
        }

        $data = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $from = CarbonImmutable::parse($data['from']);
        $to = CarbonImmutable::parse($data['to'] ?? $data['from']);
        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }
        if ($from->diffInDays($to) > 366) {
            $from = $to->subDays(366);
        }

        return [$from, $to, null];
    }
}
