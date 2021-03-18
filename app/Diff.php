<?php

use Jfcherng\Diff\DiffHelper;
use Jfcherng\Diff\Renderer\RendererConstant;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
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


        // the renderer class options
        $rendererOptions = [
            // how detailed the rendered HTML in-line diff is? (none, line, word, char)
            'detailLevel' => 'word',
            'lineNumbers' => true,
            'separateBlock' => true,
            'showHeader' => true,
            'cliColorization' => RendererConstant::CLI_COLOR_DISABLE,
        ];

        foreach ($urls as $i => $url) {
            $url = trim(parse_url($url)['path'] ?? '/', '/');
            $source1 = $this->normalise($this->get($host1, $url));
            $source2 = $this->normalise($this->get($host2, $url));
            echo "TESTING: $url  \n";

            if ($source1 == $source2) {
                echo "PASSED\n";
            } else {
                !is_dir("temp/$i") && mkdir("temp/$i");
                file_put_contents("temp/$i/source1.html", $source1);
                file_put_contents("temp/$i/source2.html", $source2);
//                echo DiffHelper::calculate($source1, $source2, 'Unified');
                echo "FAILED: diff temp/$i/source1.html temp/$i/source2.html\n" ;

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
        $containerId = preg_match('#containerId":"([^"]+)"#', $html, $matches);

        return preg_replace(
            [
                '#fly-\d+#',
                '#alpha|qa#',
                '#"_txId":"[0-9a-f-]*",#',
                "#$matches[1]#",
            ], '', $html);
    }
}
