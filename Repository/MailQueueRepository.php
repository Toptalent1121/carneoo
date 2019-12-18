<?php

namespace App\Repository;

use App\Entity\MailQueue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method MailQueue|null find($id, $lockMode = null, $lockVersion = null)
 * @method MailQueue|null findOneBy(array $criteria, array $orderBy = null)
 * @method MailQueue[]    findAll()
 * @method MailQueue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MailQueueRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MailQueue::class);
    }

    /**
     * @return mixed
     */
    public function getEmailsToSend()
    {
        $queryBuilder = $this->createQueryBuilder('mq');

        $queryBuilder->where('mq.send_at IS NULL');

        $queryBuilder->orderBy('mq.created_at', 'ASC');

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }
}
