<?php
include("vendor/autoload.php");


$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
Sentry\init(['dsn' => getenv("SENTRY_DSN") ]);
$dotenv->required('BASE_DIR')->notempty();
$dotenv->required('DOTENV_DIR')->notempty();
$dotenv->required('BACKUP_SUBDIR');
chdir($_ENV["DOTENV_DIR"]);
exec('git pull');

if (substr($_ENV["BACKUP_SUBDIR"], -1)!="/" && strlen($_ENV["BACKUP_SUBDIR"])!=0) {
    $_ENV["BACKUP_SUBDIR"].="/";
}
if (substr($_ENV["BASE_DIR"], -1)!="/") {
    $_ENV["BASE_DIR"].="/";
}
if (substr($_ENV["DOTENV_DIR"], -1)!="/") {
    $_ENV["DOTENV_DIR"].="/";
}
$files = explode(',', $_ENV["BACKUP_FILES"] ?? ".env");
$verbose = getenv("VERBOSE");
if ($verbose === "false") {
    $verbose = false;
}
if ($verbose) {
    echo "Backing up following files:\n\t".implode("\n\t", $files)."\n";
}

$it = new RecursiveDirectoryIterator($_ENV["BASE_DIR"]);
foreach (new RecursiveIteratorIterator($it) as $file) {
    if (substr($file, 0, strlen($_ENV["DOTENV_DIR"]))!= $_ENV["DOTENV_DIR"]) {
        if (in_array(basename($file), $files)) {
            $source = $file;
            $file = substr($file, strlen($_ENV["BASE_DIR"]));
            if ($verbose) {
                echo "found ".basename($file). " in ".dirname($file)."\n";
            }
            $backup_dir = $_ENV["DOTENV_DIR"].dirname($file)."/".$_ENV["BACKUP_SUBDIR"];
            if (!file_exists($backup_dir)) {
                mkdir($backup_dir, 0755, true);
            }
            $dest = $backup_dir.basename($file);
            if ($verbose) {
                echo "\tbacking up ".$source."\n\tto ".$dest. "\n";
            }
            copy($source, $dest);
        }
    }
}
chdir($_ENV["DOTENV_DIR"]);
exec('git add .');
exec('git commit -m "'.date("y-m-d").'"');
exec('git push');
