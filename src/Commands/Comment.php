<?php
/**
 * @file
 * Contains \Larowlan\Tl\Commands\Comment.php
 */

namespace Larowlan\Tl\Commands;

use Doctrine\DBAL\Driver\Connection;
use Larowlan\Tl\Connector\Connector;
use Larowlan\Tl\Formatter;
use Larowlan\Tl\Repository\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class Comment extends Command {

  /**
   * @var \Larowlan\Tl\Connector\Connector
   */
  protected $connector;

  /**
   * @var \Larowlan\Tl\Repository\Repository
   */
  protected $repository;

  const DEFAULT_COMMENT = 'Development work';

  public function __construct(Connector $connector, Repository $repository) {
    $this->connector = $connector;
    $this->repository = $repository;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('comment')
      ->setDescription('Comment on time entries')
      ->setHelp('Comment on time entries. <comment>Usage:</comment> <info>tl comment</info>')
      ->addUsage('tl comment')
      ->addUsage('tl comment --recomment')
      ->addOption('recomment', NULL, InputOption::VALUE_NONE);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $entries = $this->repository->review(Review::ALL, TRUE);
    $helper = $this->getHelper('question');
    $last = FALSE;
    foreach ($entries as $entry) {
      if ($entry->comment && !$input->getOption('retag')) {
        continue;
      }
      $title = $this->connector->ticketDetails($entry->tid);
      $question = new Question(
        sprintf('Enter comment for slot <comment>%d</comment> [<info>%d</info>]: %s [<info>%s h</info>] [%s]',
          $entry->id,
          $entry->tid,
          $title['title'],
          $entry->duration,
          $last ?: static::DEFAULT_COMMENT
        ),
        $last ?: static::DEFAULT_COMMENT
      );
      $comment = $helper->ask($input, $output, $question);
      $this->repository->comment($entry->id, $comment);
      $last = $comment;
    }
    if (!$last) {
      $output->writeln('<error>All items already commented, use --recomment to recomment</error>');
    }
  }

}
