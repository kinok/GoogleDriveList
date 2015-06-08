<?php

namespace GoogleDriveList;

use Symfony\Component\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Class GoogleDriveListCommand
 * @package GoogleDriveList
 */
class GoogleDriveListCommand extends Command\Command
{
	/**
	 * @var array config
	 */
	protected $config;

	/**
	 * configure command
	 */
	protected function configure()
	{
		$this->addUsage(<<<EOD
Additional header list:
	headRevisionId
	iconLink
	id
	kind
	lastModifyingUserName
	lastViewedByMeDate
	markedViewedByMeDate
	md5Checksum
	mimeType
	modifiedByMeDate
	modifiedDate
	openWithLinks
	originalFilename
	ownerNames
	quotaBytesUsed
	selfLink
	shared
	sharedWithMeDate
	thumbnailLink
	title
	version
	webContentLink
	webViewLink
	writersCanShare
EOD
		);
		$this
			->setName('gdl:list')
			->setDescription('Get list of your google drive files')
			->addOption('additionalReturn', 'a',
				InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Additional return ')
			->addOption('outFile', 'o',
				InputOption::VALUE_REQUIRED, 'Output file')
			->addOption('configFile', 'c',
				InputOption::VALUE_REQUIRED, 'Config file')
		;
	}

	/**
	 * Excute command
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$additionalReturn = $input->getOption('additionalReturn');
		$outFile = $input->getOption('outFile');
		$configFile = $input->getOption('configFile');

		if (!$outFile)
			$this->usage($output);

		if (!$configFile || !file_exists($configFile))
			$this->usage($output);

		$this->config = parse_ini_file($configFile);

		$gdl = new GoogleDriveList($this->config, $additionalReturn, $outFile);

		$output->write('Fetching file list... ');
		$gdl->listFiles();
		$output->writeln('done!');

		$progress = new ProgressBar($output, count($gdl->result));
		$output->write('Fetching file path... ');
		foreach ($gdl->result as $file) {
			$gdl->fetchFile($file);
			$progress->advance();
		}
		$output->writeln('done!');
	}

	protected function usage(OutputInterface $output)
	{
		$output->writeln(<<<EOD
Usage ./GoogleDriveList gd:list -c config.ini -o result.csv [-a date]
	--configFile=|-c config.ini : config file (ini format)
	--outFile=|-o result.csv : result file (csv format)
	--additionalReturn|-a header : additional header to be return (this can be repeated several times), full list below :
			headRevisionId
			iconLink
			id
			kind
			lastModifyingUserName
			lastViewedByMeDate
			markedViewedByMeDate
			md5Checksum
			mimeType
			modifiedByMeDate
			modifiedDate
			openWithLinks
			originalFilename
			ownerNames
			quotaBytesUsed
			selfLink
			shared
			sharedWithMeDate
			thumbnailLink
			title
			version
			webContentLink
			webViewLink
			writersCanShare
EOD
	);
		exit(1);
	}
}