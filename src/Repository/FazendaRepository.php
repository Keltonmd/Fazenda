<?php

namespace App\Repository;

use App\Entity\Fazenda;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Fazenda>
 */
class FazendaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Fazenda::class);
    }

    public function buscarPorUsuario(int $idUsuario): array {
        return $this->createQueryBuilder('f')
            ->join('f.usuario', 'u')
            ->where('u.id = :idUsuario')
            ->setParameter('idUsuario', $idUsuario)
            ->getQuery()
            ->getResult();
    }

    public function existePorUsuarioENome(int $idUsuario, string $nome): bool
    {
        return (bool) $this->createQueryBuilder('f')
            ->select('1')
            ->join('f.usuario', 'u')
            ->where('u.id = :idUsuario')
            ->andWhere('LOWER(f.nome) = LOWER(:nome)')
            ->setParameter('idUsuario', $idUsuario)
            ->setParameter('nome', $nome)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function contarPorUsuario(int $idUsuario): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->join('f.usuario', 'u')
            ->where('u.id = :idUsuario')
            ->setParameter('idUsuario', $idUsuario)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return Fazenda[] */
    public function buscarUltimasPorUsuario(int $idUsuario, int $limite = 5): array
    {
        return $this->createQueryBuilder('f')
            ->join('f.usuario', 'u')
            ->where('u.id = :idUsuario')
            ->setParameter('idUsuario', $idUsuario)
            ->orderBy('f.id', 'DESC')
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();
    }

    // Query para KnpPaginatorBundle
    public function buscarPorUsuarioQuery(int $idUsuario, $search)
    {
        $qb = $this->createQueryBuilder('f')
            ->join('f.usuario', 'u')
            ->where('u.id = :idUsuario')
            ->setParameter('idUsuario', $idUsuario)
            ->orderBy('f.id', 'DESC');

        if ($search) {
            $qb->andWhere('
                (
                    LOWER(f.nome) LIKE :search
                    OR LOWER(f.responsavel) LIKE :search
                )
            ')
            ->setParameter('search', '%' . strtolower($search) . '%');
        }

        return $qb;
    }
}
