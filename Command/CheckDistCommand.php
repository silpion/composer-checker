<?php

/*
 * This file is part of the Silpion/ComposerChecker package.
 *
 * (c) Julius Beckmann <beckmann@silpion.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silpion\ComposerChecker\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for checking the dist urls.
 *
 * @author Julius Beckmann <beckmann@silpion.de>
 */
class CheckDistCommand extends Command
{
    protected function configure()
    {
        $this
        ->setName('check:dist')
        ->setDescription('Matching the dist urls in a composer.lock file against some patterns.')
        ->addArgument('file', InputArgument::REQUIRED, 'composer.lock file to check')
        ->addOption(
        'url-pattern',
            'p',
        InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            'Regex-Patterns for dist-urls.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $patterns = $input->getOption('url-pattern');
        if (!$patterns) {
            $output->writeln('<error>Need at least one url-pattern.</error>');

            return 1;
        }

        $content = @file_get_contents($input->getArgument('file'));
        if (!$content) {
            $output->writeln('<error>File not found.</error>');

            return 1;
        }

        $json = @json_decode($content);
        if (!is_object($json) || json_last_error() != JSON_ERROR_NONE) {
            $output->writeln('<error>Invalid JSON in file.</error>');

            return 1;
        }

        $errors = $this->searchUrlPatterns($json, $patterns, $output);
        if ($errors) {
            $rows = array();
            foreach ($errors as $package => $url) {
                $rows[] = array($package, $url);
            }

            /** @var \Symfony\Component\Console\Helper\TableHelper $table */
            $table = $this->getApplication()->getHelperSet()->get('table');
            $table->setHeaders(array('Package', 'Dist-URL'))->setRows($rows);

            $output->writeln('<error> --- Invalid urls found --- </error>');
            $table->render($output);

            return 1;
        }

        $output->writeln('<info>All urls valid.</info>');
    }

    /**
     * Will return a array of invalid packages and their dist-urls determined by the given patterns.
     * A dist-url is invalid if NONE of the given patterns has matched.
     *
     * @param \stdClass $json
     * @param array $patterns
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return array
     */
    protected function searchUrlPatterns(\stdClass $json, array $patterns, OutputInterface $output)
    {
        $errors = array();
        foreach ($json->packages as $package) {
            if (!isset($package->dist)) {
                $output->writeln('Dist not found in "' . $package->name . '"');
            }
            $dist = $package->dist;

            $matched = false;

            foreach ($patterns as $pattern) {
                $regex = '|' . $pattern . '|';
                $this->verbose(
                     $output,
                     "Checking dist url '" . $dist->url . "' with regex '" . $regex . "' -> ",
                         OutputInterface::VERBOSITY_VERBOSE
                );
                if (preg_match($regex, $dist->url)) {
                    $this->verbose($output, "MATCHED\n", OutputInterface::VERBOSITY_VERBOSE);
                    $matched = true;
                    break;
                } else {
                    $this->verbose($output, "NOT matched\n", OutputInterface::VERBOSITY_VERBOSE);
                }
            }

            if (!$matched) {
                $errors[$package->name] = $dist->url;
            }
        }

        return $errors;
    }

    /**
     * Verbose output helper.
     *
     * @param OutputInterface $output
     * @param $message
     * @param $level
     */
    private function verbose(OutputInterface $output, $message, $level)
    {
        if ($output->getVerbosity() >= $level) {
            $output->write($message);
        }
    }
}
 