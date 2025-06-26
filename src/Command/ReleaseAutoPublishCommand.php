<?php

namespace App\Command;

use App\Entity\Release;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'release:auto-publish',
    description: 'Автоматически публикует релизы, если наступило их время.'
)]
class ReleaseAutoPublishCommand extends Command
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = new \DateTime();
        $repo = $this->em->getRepository(Release::class);

        // Получаем все релизы, которые не опубликованы и дата+время меньше текущего
        $qb = $repo->createQueryBuilder('r');
        $qb->where('r.isReleased = 0')
            ->andWhere('CONCAT(r.date, \' \' , r.time) <= :now')
            ->setParameter('now', $now->format('Y-m-d H:i:s'));

        $releases = $qb->getQuery()->getResult();

        foreach ($releases as $release) {
            $release->setIsReleased(true);
        }

        $this->em->flush();

        $output->writeln(count($releases) . ' релиз(ов) опубликовано.');
        return Command::SUCCESS;
    }
} 