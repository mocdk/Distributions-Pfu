<?php
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\SimpleWorkflow;

$application = new \TYPO3\Surf\Application\TYPO3\Flow();
$application->setOption('repositoryUrl', 'git@github.com:mocdk/Distributions-Pfu.git');
$application->setOption('localPackagePath', FLOW_PATH_DATA . 'Checkout' . DIRECTORY_SEPARATOR . $deployment->getName() . DIRECTORY_SEPARATOR . 'Preprod' . DIRECTORY_SEPARATOR);
$application->setOption('composerCommandPath', 'composer');
$application->setOption('transferMethod', 'rsync');
$application->setOption('packageMethod', 'git');
$application->setOption('updateMethod', NULL);
$application->setContext('Production/Staging');
$application->setDeploymentPath('/dana/data/pfu/');
$application->setOption('keepReleases', 2);
$application->setOption('rsyncFlags', '--recursive --times --perms --links --delete'); // Keep .git information
$deployment->addApplication($application);

$workflow = new SimpleWorkflow();
$workflow->setEnableRollback(TRUE);

// Remove resource links since they're absolute symlinks to previous releases (will be generated again automatically)
/* This requires that files are actually present, otherwise the deployment will fail. So for now, this step is disabled.
$workflow->defineTask('typo3.surf:shell:unsetResourceLinks',
	'typo3.surf:shell',
	array('command' => 'cd {releasePath} && rm -rf Web/_Resources/Persistent/*')
);
$workflow->beforeStage('switch', array('typo3.surf:shell:unsetResourceLinks'), $application);
*/


$deployment->setWorkflow($workflow);
$deployment->onInitialize(function() use ($workflow, $application) {
	$workflow->setTaskOptions('typo3.surf:generic:createDirectories', array('directories' => array('shared/Data/Web/_Resources', 'shared/Data/Session')));
	$workflow->setTaskOptions('typo3.surf:generic:createSymlinks', array(
		'symlinks' => array(
			'Web/_Resources' => '../../../shared/Data/Web/_Resources/',
			'Data/Session' => '../../../shared/Data/Session/'
		)
	));
});

$node = new Node('Staging');
$node->setHostname('bancos.pil.dk');
$node->setOption('username', 'pfu');
$application->addNode($node);