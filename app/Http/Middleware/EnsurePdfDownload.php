<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePdfDownload
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->has('pdf') && $request->get('pdf') == '1' && $response instanceof \Illuminate\Http\Response) {
            $html = $response->getContent();

            // We generate the PDF exactly matching screen rendering layout, using browser behavior
            try {
                $browsershot = \Spatie\Browsershot\Browsershot::html($html)
                    ->format('A4')
                    ->margins(10, 10, 18, 10)
                    ->showBackground()
                    ->noSandbox()
                    ->timeout(120);

                // Use local Chrome to prevent issues on windows environment
                $chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
                if (!file_exists($chromePath)) {
                    $chromePath = 'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe';
                }
                if (file_exists($chromePath)) {
                    $browsershot->setChromePath($chromePath);
                }

                $pdfContent = $browsershot->pdf();

                if (ob_get_length()) {
                    ob_clean();
                }

                return response($pdfContent, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="document_'.time().'.pdf"',
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Browsershot PDF generation failed: " . $e->getMessage());
                // Fallback to normal HTML page response but maybe without js generator
            }
        }

        return $response;
    }
}
