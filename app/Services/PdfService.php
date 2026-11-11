<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

/**
 * PDF generation for quotations, invoices and receipts.
 */
class PdfService
{
    public static function quotation(\App\Models\Quotation $quotation, string $view = 'erp.pdf.quotation')
    {
        $quotation->load(['client', 'items', 'creator']);

        return Pdf::loadView($view, compact('quotation'))
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', true);
    }

    public static function invoice(\App\Models\Invoice $invoice, string $view = 'erp.pdf.invoice')
    {
        $invoice->load(['client', 'items', 'creator']);

        return Pdf::loadView($view, compact('invoice'))
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', true);
    }

    public static function receipt(\App\Models\Payment $payment, string $view = 'erp.pdf.receipt')
    {
        $payment->load(['client', 'recorder', 'invoices']);

        return Pdf::loadView($view, compact('payment'))
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', true);
    }

    public static function payslip(\App\Models\PayrollRecord $payroll, string $view = 'erp.pdf.payslip')
    {
        $payroll->load(['employee.user']);

        return Pdf::loadView($view, compact('payroll'))
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', true);
    }
}