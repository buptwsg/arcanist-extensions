<?php

final class ArcanistESLintLinter extends ConfigPathLinter {

    public function getDefaultBinary() {
        $config = $this->getEngine()->getConfigurationManager();
        return $config->getConfigFromAnySource('lint.eslint.bin', 'eslint');
    }

    public function getMandatoryFlags() {
        return array(
            '--format', 'checkstyle'
        );
    }

    public function getInstallInstructions() {
        return 'Install eslint via npm (npm install -g eslint). '
            . 'Much obliged.';
    }

    public function getLinterName() {
        return 'eslint';
    }

    public function getLinterConfigurationName() {
        return 'eslint';
    }

    public function shouldExpectCommandErrors() {
        return true;
    }

    protected function parseLinterOutput($path, $err, $stdout, $stderr) {
        $messages = array();
        $report_dom = new DOMDocument();

        $ok = $report_dom->loadXML($stdout);
        if (!$ok) {
            return false;
        }

        $files = $report_dom->getElementsByTagName('file');
        foreach ($files as $file) {
            foreach ($file->childNodes as $child) {
                if (!($child instanceof DOMElement)) {
                    continue;
                }

                $severity = $child->getAttribute('severity');
                if ($severity == 'error') {
                    $prefix = 'E';
                } else {
                    $prefix = 'W';
                }

                $code = 'ESLINT.'.$prefix.'.'.$child->getAttribute('source');

                $message = new ArcanistLintMessage();
                $message->setPath($path);
                $message->setLine($child->getAttribute('line'));
                $message->setChar($child->getAttribute('column'));
                $message->setCode($code);
                $message->setDescription($child->getAttribute('message'));
                $message->setSeverity($this->getLintMessageSeverity($code));

                $messages[] = $message;
            }
        }

        return $messages;
    }

    protected function getDefaultMessageSeverity($code) {
        if (preg_match('/^ESLINT\\.W\\./', $code)) {
            return ArcanistLintSeverity::SEVERITY_WARNING;
        } else {
            return ArcanistLintSeverity::SEVERITY_ERROR;
        }
    }

}
