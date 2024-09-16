<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Smalot\PdfParser\Parser;

class ProcessVcb11Transaction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vcb-11092024:handle {filename}';

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
                '/(?=\d{2}\/\d{2}\/\d{4})/',
                $text,
                -1,
                PREG_SPLIT_NO_EMPTY
            );

            foreach ($blocks as $block) {
                $block = trim($block);

                // Remove the last line of block (order number)
                $lines = explode(PHP_EOL, $block);
                array_pop($lines);

                $block = implode(PHP_EOL, $lines);

                if (! preg_match('/\d{2}\/\d{2}\/\d{4}/', $block, $dates)) {
                    continue;
                }

                $date = $dates[0];
                $donatedDate = Carbon::createFromFormat('d/m/Y', $date);

                // Remove date from block
                $block = str_replace($date, '', $block);

                if (! preg_match('/\d{1,3}(?:\.\d{3})+/', $block, $amounts)) {
                    continue;
                }

                // Remove amount from block
                $block = str_replace($amounts[0], '', $block);

                // Remove leading and trailing spaces
                $description = substr(trim(preg_replace(['/"/', '/\s+/'], ' ', $block)), 0, 254);
                $amount = str_replace('.', '', $amounts[0]);

                try {
                    Transaction::create([
                        'donated_at' => $donatedDate,
                        'bank' => 'vcb',
                        'amount' => $amount,
                        'description' => $description,
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
