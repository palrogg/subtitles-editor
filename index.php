<html>
<head>
  <meta charset="utf-8">
  <link href="https://fonts.googleapis.com/css?family=Montserrat:400,600" rel="stylesheet">
  <style>
  *{
    font-family: 'Montserrat', sans-serif;
  }
  p{
    width: auto;
    white-space: pre;
  }
  #subtitle_container{
    border: 2px solid #ccc;
  }
  .PIRATE_ANIME_BORDEAU{
    display: none;
  }
  .LOWER_THIRD, .OUTRO{
    border-left: 1em solid steelblue;
    margin-left: 2em;
    padding-left: .5em;
  }
  .LOWER_THIRD span, .OUTRO span{
    background: #ccc;
  }
  span.attrName{
    margin-left: 1em;
    padding: .2em .5em;
    background: #222;
    font-size: .7em;
    color: #fff;
  }
  span.attrName.Move_Right_All{
    display: none;
  }
  </style>
</head>
<body>

  <?php

  define('CANONICAL_URL', 'http://localhost/~rnp/subtitles-reader/'); // TODO change for labs.letemps.ch
  /*
  Global flags
  */
  // Check for GET parameter
  $uploaded_file_parameter = isset($_GET['file']) ? $_GET['file'] : false;
  // Check for subtitle upload / share
  $display_subtitles = false;

  /*
  SubtitleFile Class
  */
  class SubtitleFile
  {
    public $filename = "";
    public $xml_dom;

    public function setName($newval)
    {
      $this -> filename = $newval;
    }
    public function setNameWithoutExtension($newval)
    {
      $this -> filename = $newval . '.fcpxml';
    }

    public function loadXML(){
      $_dom = new DOMDocument;
      set_error_handler( function($w) {
        echo "<b>Le chargement du fichier a échoué ou généré un avertissement</b> (type $w)";
      } );
      try {
        $_dom -> load( $this -> getPath() );
      }
      catch (Exception $e) {
        echo '<b>Le chargement du fichier a échoué:</b> ',  $e->getMessage(), "\n";
      }
      restore_error_handler();
      $this -> xml_dom = $_dom;
    }
    public function getTitles(){
      return $this -> xml_dom -> getElementsByTagName('title');
    }
    // TODO later: save XML
    // using $_dom->saveXML();

    public function getName()
    {
      return $this->filename;
    }

    public function getPath()
    {
      return 'files/' . $this->filename;
    }

    public function getExtension()
    {
      return strtolower(end(explode('.', $this->filename)));
    }

    public function getNameWithoutExtension()
    {
      return strtok($this->filename, '.');
    }

  }
  /*
  End Class
  */

  // if form sent: upload file
  if(isset($_FILES['fcp_file'])){
    $errors= array();
    $file_name = $_FILES['fcp_file']['name'];
    $file_size = $_FILES['fcp_file']['size'];
    $file_tmp = $_FILES['fcp_file']['tmp_name'];
    $file_type = $_FILES['fcp_file']['type'];
    $file_ext = strtolower(end(explode('.', $_FILES['fcp_file']['name'])));

    // TODO later: add txt if we allow text documents to generate subtitles
    $extensions = array("fcpxml");

    if(in_array($file_ext, $extensions) === false){
      $errors[] = "le fichier doit être un .fcpxml"; // TODO later: ou txt
    }

    if($file_size > 2097152) {
      $errors[] = 'le fichier doit faire moins de 2 Mo';
    }

    if(empty($errors)==true) {
      move_uploaded_file($file_tmp, "files/" . $file_name);
      $subtitle_file = new SubtitleFile;
      $subtitle_file -> setName($file_name);
      $display_subtitles = true;
    }else{
      echo '<p>Une erreur s’est produite:</p><ul><li>';
      echo join("</li><li>", $errors);
      echo '</li></ul>';
    }
  } elseif($uploaded_file_parameter) { // if GET parameter: store filename
    $subtitle_file = new SubtitleFile;
    $subtitle_file -> setNameWithoutExtension($uploaded_file_parameter);
    $display_subtitles = true;
  }

  // display subtitles
  if( $display_subtitles == true) {
    ?>

    <h1>Sous-titres</h1>
    <ol>
      <li>Partager / éditer les sous-titres</li>
      <li>Copier le texte corrigé pour l’envoyer par e-mail avec le bouton «copier» ci-dessous</li>
    </ol>

    <?php
    $subtitle_file -> loadXML();

    $sharelink = CANONICAL_URL . '?file=' . $subtitle_file -> getNameWithoutExtension();
    echo "<p>Lien de partage: <a href=\"$sharelink\">$sharelink</a></p>";
    echo "<button onclick=\"copyContent()\">Copier</button>";
    echo '<div id="subtitle_container">';


    foreach ($subtitle_file -> getTitles() as $title) {
      $attrName = $title->getAttribute('name');
      // Get last occurrence of " - "
      if (($pos = strrpos($attrName, " - ")) !== FALSE) {
        $attrName = substr($attrName, $pos+2);
      }
      $attrName = str_replace(" ", "_", trim($attrName));
      $content = trim($title -> nodeValue);

      if($attrName == 'OUTRO'){
        if (($pos = strrpos($content, "DéCOCHER CE BLOC!")) !== FALSE) {
          $content = substr($content, $pos+18);
        }
      }
      echo "<p class=\"$attrName\"><span contenteditable=\"true\">$content</span> <span class=\"attrname $attrName\">$attrName</span></p>" . PHP_EOL;
    }
    echo '</div>';
    echo '<h2>Charger d’autres sous-titres:</h2>';

  }else{
    echo '<h1>Charger un sous-titre</h1>';
  }
  ?>

    <!-- Intro si pas de fichier chargé (ni GET ni upload) -->
    <ol>
      <li>Dans Final Cut Pro, ouvrir le menu <b>Fichier</b> puis <b>Exporter au format XML</b></li>
      <li>Uploader le fichier créé</li>
    </ol>
    <form action="" method="POST" enctype="multipart/form-data">
      <input id="file" type="file" name="fcp_file" />
      <input type="submit" value="Go">
    </form>

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>

    <script>
    // Edits in red
    function setColor(color) {
      document.execCommand('styleWithCSS', false, true);
      document.execCommand('foreColor', false, color);
    }
    $('#subtitle_container').on('keypress', function(){
      document.execCommand('styleWithCSS', false, true);
      document.execCommand('foreColor', false, '#f00');
    });

    // Copy content
    function copyContent() {
      var el = document.getElementById("subtitle_container");
      var range = document.createRange();
      range.selectNodeContents(el);
      var sel = window.getSelection();
      sel.removeAllRanges();
      sel.addRange(range);
      document.execCommand("copy");
    }

    // Auto submit
    $('#file').change(function() {
      $('form').submit();
    });
  </script>

  </body>
  </html>
