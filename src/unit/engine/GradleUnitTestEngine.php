<?php
/**
 *
 */

final class GradleUnitTestEngine extends ArcanistUnitTestEngine {

    final protected function buildCommand($paths) {
        $binary = $this->getDefaultBinary();
        $args = implode(' ', array_merge(
            $this->getMandatoryFlags(), $this->getDefaultFlags()));
        $paths = implode(' ', $paths);
        return "$binary $args $paths";
    }

    final protected function findTestXmlFiles() {
        $base = getcwd();
        $directory = new RecursiveDirectoryIterator($base);
        $iterator = new RecursiveIteratorIterator($directory);
        $regex = new RegexIterator($iterator,
            '/^.+build\/test-results\/.*\/TEST.*\.xml$/i',
            RecursiveRegexIterator::GET_MATCH);
        $matches = iterator_to_array($regex);
        $files = array();
        foreach ($matches as $match) {
            $files[] = $match[0];
        }
        if (!count($files)) {
            throw new ArcanistUsageException('Could not find any test output '
                . 'files. Check this project is correctly configured and '
                . 'actually a Gradle project.');
        }
        return $files;
    }

    final protected function extractErrors($files) {
        $errors = array();

        foreach ($files as $report) {
            $results = $this->parseReport($report);
            foreach ($results as $error) {
                $errors[] = $error;
            }
        }

        return $errors;

    }

    final protected function parseReport($report) {
        $parser = new ArcanistXUnitTestResultParser();
        $results = $parser->parseTestResults(
            Filesystem::readFile($report));
        return $results;
    }

    public function getDefaultBinary() {
        $config = $this->getConfigurationManager();
        return $config->getConfigFromAnySource('bin.gradle', 'gradle');
    }

    public function getMandatoryFlags() {
        return array('test', '--daemon');
    }

    public function getDefaultFlags() {
        $config = $this->getConfigurationManager();
        return $config->getConfigFromAnySource('unit.gradle.options', array());
    }

    public function run() {
        $working_copy = $this->getWorkingCopy();
        $root = $working_copy->getProjectRoot();
        chdir($root);

        // exec gradle
        $result = exec_manual($this->buildCommand(array()));

        // find files and parse results
        $files = $this->findTestXmlFiles();

        $errors = $this->extractErrors($files);
        return $errors;
    }
}
