<?php

namespace App\Repository\Webapp;

use App\Entity\Webapp\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Article|null find($id, $lockMode = null, $lockVersion = null)
 * @method Article|null findOneBy(array $criteria, array $orderBy = null)
 * @method Article[]    findAll()
 * @method Article[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function carousel()
    {
        return $this->createQueryBuilder('a')
            ->select('a.id, a.title')
            ->join('a.category', 'c')
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(4)
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * Retourne seulement un article de l'entité
     * @return Article[] Returns an array of Article objects
     **/
    public function OneArticle($id)
    {
        return $this->createQueryBuilder('a')
            ->join('a.category', 'c')
            ->join('a.linkPage', 'p')
            ->addSelect('
                a.id AS id,
                a.isShowtitle AS isShowtitle,
                a.title AS title,
                a.slug AS slug,
                a.articleFrontName AS articleFrontName,
                a.isReadMore AS isReadMore,
                a.content AS content,
                a.isLink AS isLink,
                a.Linktext AS linktext,
                p.slug AS linkPage,
                a.articleFrontPosition AS articleFrontPosition,
                a.isShowdate AS isShowdate,
                a.createdAt AS createdAt
                ')
            //->andWhere('a.id = :id')
            //->setParameter('id', $id)
            ->orderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    // /**
    //  * @return Article[] Returns an array of Article objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Article
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
