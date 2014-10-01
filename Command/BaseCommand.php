<?php
/**
 * Created by PhpStorm.
 * User: julius
 * Date: 01.10.14
 * Time: 11:48
 */

namespace Silpion\ComposerChecker\Command;

use Camspiers\JsonPretty\JsonPretty;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommand extends Command
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var InputInterface
     */
    protected $input;

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * Verbose output helper.
     *
     * @param $message
     * @param $level
     */
    protected function verbose($message, $level)
    {
        if ($this->output->getVerbosity() >= $level) {
            $this->output->write($message);
        }
    }

    protected function printTable($rows, $title =  ' --- Invalid urls found --- ', array $headers = array('Package', 'Src-URL'))
    {
        /** @var \Symfony\Component\Console\Helper\TableHelper $table */
        $table = $this->getApplication()->getHelperSet()->get('table');
        $table->setHeaders($headers)->setRows($rows);

        $this->output->writeln('<error>'.$title.'</error>');
        $table->render($this->output);
    }

    /**
     * Will return true, if the given url matches at least one of the given patterns.
     *
     * @param $url
     * @param array $patterns
     * @return bool
     */
    protected function doesUrlMatchToAtLeastOnePattern($url, array $patterns)
    {
        foreach ($patterns as $pattern) {
            $regex = '|' . $pattern . '|';
            $this->verbose(
                "Checking url '" . $url . "' with regex '" . $regex . "' -> ",
                OutputInterface::VERBOSITY_VERBOSE
            );
            if (preg_match($regex, $url)) {
                $this->verbose("MATCHED\n", OutputInterface::VERBOSITY_VERBOSE);
                return true;
            } else {
                $this->verbose("NOT matched\n", OutputInterface::VERBOSITY_VERBOSE);
            }
        }
        return false;
    }

    protected function readJsonFromFile($path)
    {
        $content = @file_get_contents($path);
        if (!$content) {
            throw new \Exception("File not found or empty.");
        }

        $json = @json_decode($content);
        if (!is_object($json) || json_last_error() != JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON in file.");
        }

        return $json;
    }

    protected function updateJsonContentInFile($path, \stdClass $content)
    {
        array_unshift($content->_readme, '------------------------------------------------------------------');
        array_unshift($content->_readme, 'ATTENTION: This file has been changed by silpion/composer-checker.');
        array_unshift($content->_readme, '------------------------------------------------------------------');

        // Write new content to same file
        $jsonPretty = new JsonPretty();
        file_put_contents($path, $jsonPretty->prettify($content));

        $this->output->writeln('<info>Updated file.</info>');
    }
} 