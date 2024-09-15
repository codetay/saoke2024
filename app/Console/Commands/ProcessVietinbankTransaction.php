<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Smalot\PdfParser\Parser;
use Spatie\PdfToText\Pdf;

class ProcessVietinbankTransaction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vietinbank:handle {filename} {--binPath=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handle Vietinbank transaction data file';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(): void
    {

        $file = $this->argument('filename');
        $binPath = $this->option('binPath');

        $filepath = database_path('transactions/'.$file);

        if (! file_exists($filepath)) {
            $this->error('Error: Transaction file not found!');

            return;
        }

        // Get page info
        $parser = new Parser;
        $pdf = $parser->parseFile($filepath);

        $totalPage = count($pdf->getPages());
        for ($i = 1; $i <= $totalPage; $i++) {
            $text = (new Pdf($binPath))
                ->setPdf($filepath)
                ->setOptions(['layout', '-f '.$i, '-l '.$i])
                ->text();

            $blocks = preg_split(
                "/(?=\d{2}\/\d{2}\/\d{4})/",
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

                // Remove datetime from block
                $block = preg_replace('/\d{2}:\d{2}:\d{2}/', '', $block);

                // Remove leading and trailing spaces
                $description = preg_replace('/\s+/', ' ', $block);

                $amount = str_replace('.', '', $amounts[0]);

                try {
                    Transaction::create([
                        'donated_at' => $donatedDate,
                        'bank' => 'icb',
                        'amount' => $amount,
                        'description' => $description,
                    ]);
                } catch (QueryException $e) {
                    $this->error(sprintf('Error: %s', $e->getMessage()));
                    break;
                }
            }
        }
    }
}
