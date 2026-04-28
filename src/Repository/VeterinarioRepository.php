<?php

namespace App\Repository;

use App\Entity\Veterinario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Veterinario>
 */
class VeterinarioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Veterinario::class);
    }

     public function existePorUsuarioECrmv(int $idUsuario, string $crmv): bool {
        return (bool) $this->createQueryBuilder('v')
            ->select('1')
            ->where('v.usuario = :idUsuario')
            ->andWhere('LOWER(v.crmv) = LOWER(:crmv)')
            ->setParameter('idUsuario', $idUsuario)
            ->setParameter('crmv', $crmv)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function contarPorUsuario(int $idUsuario): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.usuario = :idUsuario')
            ->setParameter('idUsuario', $idUsuario)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return Veterinario[] */
    public function buscarUltimosPorUsuario(int $idUsuario, int $limite = 5): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.usuario = :idUsuario')
            ->setParameter('idUsuario', $idUsuario)
            ->orderBy('v.id', 'DESC')
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();
    }
    
    ## Querys Para KnpPaginatorBundle
    public function buscarPorUsuarioQuery(int $idUsuario, $search) {
        $qb = $this->createQueryBuilder('v')
            ->where('v.usuario = :idUsuario')
            ->setParameter('idUsuario', $idUsuario)
            ->orderBy('v.id', 'DESC');

        if ($search) {
            $qb->andWhere('
                (
                    LOWER(v.nome) LIKE :search
                    OR LOWER(v.crmv) LIKE :search
                )
            ') 
            ->setParameter('search', '%' . strtolower($search) . '%');
        }


        return $qb;
    }
}
