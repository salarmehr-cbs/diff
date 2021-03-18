<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Diff extends Command
{
    protected static $defaultName = 'diff';

    protected function configure()
    {
        $this->setDescription('Diff between two URLs')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Diff between two URLs')
            ->addArgument('file', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->compare('https://qa.cbssports.com', 'https://alpha.cbssports.com', $input->getArgument('file'));

        return Command::SUCCESS;
    }

    private function compare($host1, $host2, $file)
    {
        $urls = file($file, FILE_IGNORE_NEW_LINES);
        $results = [];

        foreach ($urls as $i => $url) {
            $i++;
            $url = trim(parse_url($url)['path'] ?? '/', '/');
            $source1 = $this->normalise($this->get($host1, $url));
            $source2 = $this->normalise($this->get($host2, $url));
            echo "TESTING($i/" . count($urls) . ") $url\n";

            $file1 = "temp/$i/source1.html";
            $file2 = "temp/$i/source2.html";

            if ($source1 == $source2) {
                echo "✓  PASSED\n";
                is_dir("temp/$i") && unlink($file1) && unlink($file2) && rmdir("temp/$i");
            } else {
                !is_dir("temp/$i") && mkdir("temp/$i");
                file_put_contents($file1, $source1);
                file_put_contents($file2, $source2);
                echo "✗ FAILED: diff $file1 $file2\n";
            }

        }

        return $results;
    }

    private function get($url, $path)
    {
        $client = new GuzzleHttp\Client();

        return $client->request('GET', "$url/$path", [
            'auth' => ['beta', 'test'],
        ])->getBody()->getContents();
    }

    private function normalise($html)
    {
        preg_match('#containerId":"([^"]+)"#', $html, $matches);

        return preg_replace(
            [
                '#fly-\d+#',
                '#alpha|qa#',
                '#"_txId":"[0-9a-f-]*",#',
                "#$matches[1]#",
            ], '', $html);
    }
}
