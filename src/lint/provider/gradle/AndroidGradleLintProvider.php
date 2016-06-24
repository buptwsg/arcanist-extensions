<?php

class AndroidGradleLintProvider implements GradleLintProvider {

  public function getName() {
    return 'android';
  }

  public function getTargets() {
    return array('lint');
  }

  public function shouldLintBinaryFiles() {
    return true;
  }

  public function shouldLintDirectories() {
    return true;
  }

  public function parseLinterOutput(array $paths) {
    $parser = new AndroidParser();
    return $parser->parseAll(
      '/build\/outputs\/lint-results.*(?<!fatal)\.xml$/i', $paths);
  }

  public function isLintDetectedMessage($error_message) {
    return strpos($error_message, 'Lint found errors in the project; aborting build.') !== false;
  }
}
