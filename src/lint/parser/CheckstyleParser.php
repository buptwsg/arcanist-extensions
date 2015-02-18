<?php

class CheckstyleParser extends AbstractFileParser {
  protected function parse($file) {
    $messages = array();
    $report_dom = new DOMDocument();
    $content = file_get_contents($file);

    $ok = $report_dom->loadXML($content);
    if (!$ok) {
      throw new Exception('Arcanist could not load the linter output. '
        . 'Either the linter failed to produce a meaningful'
        . ' response or failed to write the file.');
    }

    $files = $report_dom->getElementsByTagName('file');
    foreach ($files as $file) {
      foreach ($file->childNodes as $child) {
        if (!($child instanceof DOMElement)) {
          continue;
        }

        $severity = $child->getAttribute('severity');
        if ($severity === 'error') {
          $prefix = 'E';
        } else {
          $prefix = 'W';
        }

        $code = 'CS.'.$prefix.'.'.$child->getAttribute('source');
        $path = $file->getAttribute('name');

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

  private function getLintMessageSeverity($code) {
    if (preg_match('/^CS\\.W\\./', $code)) {
      return ArcanistLintSeverity::SEVERITY_WARNING;
    } else {
      return ArcanistLintSeverity::SEVERITY_ERROR;
    }
  }
}
