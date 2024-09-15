<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Smalot\PdfParser\Parser;

class ProcessVcbTransaction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vcb:handle {filename}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handle VCB transaction data file';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(): void
    {

        $file = $this->argument('filename');

        $filepath = database_path('transactions/'.$file);

        if (! file_exists($filepath)) {
            $this->error('Error: Transaction file not found!');

            return;
        }

        $parser = new Parser;
        $pdf = $parser->parseFile($filepath);

        $pages = $pdf->getPages();

        foreach ($pages as $pageIndex => $page) {
            $text = $page->getText();

            $blocks = preg_split(
                '/(?=\n\d{2}\/\d{2}\/\d{4}\n)/',
                $text,
                -1,
                PREG_SPLIT_NO_EMPTY
            );

            foreach ($blocks as $blockIndex => $block) {
                // Skip header block of first page
                if ($blockIndex === 0) {
                    continue;
                }

                $block = trim($block);
                $lines = explode(PHP_EOL, $block);

                $date = trim(array_shift($lines));
                $donatedDate = Carbon::createFromFormat('d/m/Y', $date);

                $code = trim(array_shift($lines));

                $amount = '';
                $descriptionLines = [];
                $isFoundAmount = false;

                foreach ($lines as $line) {
                    $line = trim($line);

                    if (! $isFoundAmount && preg_match("/^\d+\.\d+\b/", $line, $matches)) {
                        $isFoundAmount = true;
                        $amount = str_replace('.', '', $matches[0]);
                        $descriptionLines[] = substr($line, strlen($amount) + 1);
                    } else {
                        $descriptionLines[] = $line;
                    }
                }

                $description = preg_replace('/\s+/', ' ', implode(' ', array_filter($descriptionLines)));

                // Remove last page number from content
                $descriptionParts = preg_split(
                    "/page\s+\d+\s+of\s+\d+/i",
                    $description,
                    -1,
                    PREG_SPLIT_NO_EMPTY
                );

                try {
                    Transaction::updateOrCreate([
                        'donated_at' => $donatedDate,
                        'code' => $code,
                    ], [
                        'amount' => $amount,
                        'description' => trim($descriptionParts[0]),
                    ]);
                } catch (QueryException $e) {
                    $this->error(sprintf('Error: %s', $e->getMessage()));
                    break 2;
                }

            }

            $this->info(sprintf('Page %d processed successfully!', $pageIndex + 1));
        }
    }
}
