<?php

require __DIR__ . '/vendor/autoload.php';

$dir = null;

$depth = PHP_INT_MAX;

$copydir = '/tmp/copydir';

foreach ( $argv as $i => $arg )
{
	if ( $i == 0 )
		continue;

	$explodedArg = explode( '=', $arg );
	if ( $explodedArg[0] === 'dir' && isset( $explodedArg[1] ) )
	{
		$dir = $explodedArg[1];
	}

	if ( $explodedArg[0] === 'depth' && isset( $explodedArg[1] ) )
	{
		$depth = (int)$explodedArg[1];
	}

	if ( $explodedArg[0] === 'copydir' && isset( $explodedArg[1] ) )
	{
		$copydir = (string)$explodedArg[1];
	}
}

if ( $dir === null )
{
	$dir = __DIR__;
}

if ( ! is_dir( $copydir ) )
	mkdir( $copydir, 0777, true );

$it = new Zakirovir6\Directory\IteratorRecursive( $dir, $depth );
$fileInfo = new Zakirovir6\Directory\FileInfo();

$errors = [];

foreach ( $it as $key => $file )
{
	$metaFile = $fileInfo->Get( $file );
	if ( $metaFile === null )
	{
		$description = sprintf( 'not found, error: %s', $fileInfo->getErrors() );
		$fileInfo->FlushErrors();
	}

	$type = '0';
	if ( $fileInfo->isSymlink( $metaFile ) )
		$type = 'l';
	if ( $fileInfo->isFile( $metaFile ) )
		$type = 'f';
	if ( $fileInfo->isDirectory( $metaFile ) )
		$type = 'd';

	$description = sprintf( 'type: %s, a: %s, c: %s, m: %s', $type, date( 'd.m.Y H:i:s', $metaFile->atime ), date( 'd.m.Y H:i:s', $metaFile->ctime ), date( 'd.m.Y H:i:s', $metaFile->mtime ) );

	echo sprintf( '%s %s - %s [mem: %d]', $key, $file, $description, memory_get_usage() );
	echo PHP_EOL;

	if ( $fileInfo->isDirectory( $metaFile ) )
		continue;

	$newDir = sprintf( '%s/%s/%s', $copydir, date( 'Y', $metaFile->mtime ), date( 'd.m.Y', $metaFile->mtime ) );
	if ( ! is_dir( $newDir ) )
		mkdir( $newDir, 0777, true );

	$newFile = sprintf( '%s/%s', $newDir, basename( $file ) );

	$count = 0;
	while ( file_exists( $newFile ) )
	{
		$count++;
		$fname = explode( '.', basename( $file ) );
		$fname[0] = sprintf( '%s(%d)', $fname[0], $count );
		$fname = implode( '.', $fname );
		$newFile = sprintf( '%s/%s', $newDir, $fname );
	}

	$res = copy( $file, $newFile );
	if ( $res )
		echo sprintf( '++ %s%s', $newFile, PHP_EOL );
	else
	{
		echo '--' . PHP_EOL;
		$errors[] = print_r( error_get_last(), true );
	}
}

if ( $it->hasErrors() )
{
	foreach ( $it->getErrors() as $error )
		echo sprintf( '###ERROR: %s', $error ) . PHP_EOL;
}

if ( count( $errors ) )
{
	foreach ( $errors as $error )
		echo sprintf( '###COPY ERROR: %s', $error ) . PHP_EOL;
}