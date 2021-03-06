<?php

require_once __DIR__ . '/lib/web.php/web.php';
require_once __DIR__ . '/lib/LibRDF/LibRDF/LibRDF.php';

define('REPO', __DIR__ . '/resource/');

$routes = array(
  '/resource/:id' => 'ResourceController',
);

class ResourceController extends WebController {

  public function PUT($id) {

    $ntriples = file_get_contents("php://input");
    $ntriples_parser = new LibRDF_Parser('ntriples');
    $rdf_model = new LibRDF_Model(new LibRDF_Storage());
    $rdf_serializer = new LibRDF_Serializer('ntriples');

    try {
      $rdf_model->loadStatementsFromString($ntriples_parser, $ntriples);
      $this->_response->writeHead(200, array());
    } catch (LibRDF_Error $e) {
      $this->_response->writeHead(400, array());
    }

    $path_info = pathinfo(REPO.$id);
    if (!array_key_exists('extension', $path_info)) {
      $path_info['extension'] = 'nt';
    }
    $path = "{$path_info['dirname']}/{$path_info['filename']}.{$path_info['extension']}";

    $rdf_model->serializeStatementsToFile($rdf_serializer, $path);
    $this->__commit(
      $path_info['dirname'],
      "{$path_info['filename']}.{$path_info['extension']}",
      "Add {$path_info['filename']}.{$path_info['extension']}"
    );
    $this->_response->terminate();

  }

  public function PATCH($id) {

    $path_info = pathinfo(REPO.$id);
    if (!array_key_exists('extension', $path_info)) {
      $path_info['extension'] = 'nt';
    }
    $path = "{$path_info['dirname']}/{$path_info['filename']}.{$path_info['extension']}";

    if (file_exists($path)) {
      $diff_data = fopen("php://input", 'r');
      try {
        $this->__apply($diff_data, $path);
        $this->_response->writeHead(204, array(
          "Content-Location" => "{$path_info['filename']}.{$path_info['extension']}"
        ));
      } catch (LibRDF_Error $e) {
        // Conflicting state
        $this->_response->writeHead(409, array());
        $this->_response->write($e->getMessage());
      }
      $this->__commit(
        $path_info['dirname'],
        "{$path_info['filename']}.{$path_info['extension']}",
        "Update {$path_info['filename']}.{$path_info['extension']}"
      );
    } else {
      // Resource not found
      $this->_response->writeHead(404, array());
    }
    $this->_response->terminate();
  }

  public function DELETE($id) {

    $path_info = pathinfo(REPO.$id);
    if (!array_key_exists('extension', $path_info)) {
      $path_info['extension'] = 'nt';
    }
    $path = "{$path_info['dirname']}/{$path_info['filename']}.{$path_info['extension']}";

    if (file_exists($path)) {
      unlink($path);
    } else {
      // Resource not found
      $this->_response->writeHead(404, array());
    }

  }

  private function __apply($diff, $path) {

    $ntriples_parser = new LibRDF_Parser('ntriples');
    $rdf_model = new LibRDF_Model(new LibRDF_Storage());
    $ntriples = file_get_contents($path);
    $rdf_model->loadStatementsFromString($ntriples_parser, $ntriples);

    $removed_ntriples = new LibRDF_Model(new LibRDF_Storage());
    $added_ntriples = new LibRDF_Model(new LibRDF_Storage());
    while($diff_line = fgets($diff)) {
      $op = substr($diff_line, 0, 1);
      $ntriple = substr($diff_line, 1);
      switch($op) {
        case '+':
          $added_ntriples->loadStatementsFromString($ntriples_parser, $ntriple);
          break;
        case '-':
          $removed_ntriples->loadStatementsFromString($ntriples_parser, $ntriple);
          break;
      }
    }

    foreach ($added_ntriples as $added_triple) {
      $rdf_model->addStatement($added_triple);
    }
    foreach ($removed_ntriples as $removed_triple) {
      $rdf_model->removeStatement($removed_triple);
    }
    $rdf_serializer = new LibRDF_Serializer('ntriples');
    $rdf_model->serializeStatementsToFile($rdf_serializer, $path);

  }

  private function __commit($repo_dir, $file_name, $commit_message) {

    $command = "cd $repo_dir"
      . " && git add $file_name"
      . " && git commit "
      . " --author='Author Name <email@address.com>'"
      . " -m '$commit_message'";
    exec($command);

  }

}

$app = new WebApp();
$app->dispatch($routes);
