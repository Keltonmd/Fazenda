<?php

namespace App\Repository;

use App\Entity\Gado;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;


/**
 * @extends ServiceEntityRepository<Gado>
 */
class GadoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Gado::class);
    }

    /** @return Gado[] */
    public function buscarNaoAbatidosEPorUsuario(int $idUsuario): array {
        return $this->createQueryBuilder('g')
            ->join('g.fazenda', 'f')
            ->join('f.usuario', 'u')
            ->where('u.id = :idUsuario')
            ->andWhere('g.abatido = false')
            ->setParameter('idUsuario', $idUsuario)
            ->getQuery()
            ->getResult()
        ;
    }

    public function mandarParaAbate(array $idsGado, int $idUsuario): int {
        if (empty($idsGado)) {
            return 0;
        }

        $idsValidados = array_column(
            $this->createQueryBuilder('g')
                ->select('g.id')
                ->join('g.fazenda', 'f')
                ->join('f.usuario', 'u')
                ->where('u.id = :idUsuario')
                ->andWhere('g.id IN (:ids)')
                ->andWhere('g.abatido = false')
                ->setParameter('idUsuario', $idUsuario)
                ->setParameter('ids', $idsGado)
                ->getQuery()
                ->getArrayResult(),
            'id'
        );

        if (empty($idsValidados)) {
            return 0;
        }

        return $this->createQueryBuilder('g')
            ->update(Gado::class, 'g')
            ->set('g.abatido', ':abatido')
            ->set('g.dataAbate', ':dataAbate')
            ->where('g.id IN (:ids)')
            ->setParameter('abatido', true)
            ->setParameter('dataAbate', new \DateTimeImmutable())
            ->setParameter('ids', $idsValidados)
            ->getQuery()
            ->execute();
    }

    public function contarPorFazenda(int $idFazenda): int {
        return (int) $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->where('g.fazenda = :idFazenda')
            ->andWhere('g.abatido = false')
            ->setParameter('idFazenda', $idFazenda)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function contarPorUsuarioEStatus(int $idUsuario, bool $abatido): int
    {
        return (int) $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->join('g.fazenda', 'f')
            ->join('f.usuario', 'u')
            ->where('u.id = :idUsuario')
            ->andWhere('g.abatido = :abatido')
            ->setParameter('idUsuario', $idUsuario)
            ->setParameter('abatido', $abatido)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function contarParaAbatePorUsuario(int $idUsuario): int
    {
        $dataLimite = (new \DateTime())->modify('-5 years');

        return (int) $this->aplicarCondicoesParaAbate(
            $this->createQueryBuilder('g')
                ->select('COUNT(g.id)')
                ->join('g.fazenda', 'f')
                ->join('f.usuario', 'u')
                ->where('u.id = :idUsuario')
                ->andWhere('g.abatido = false')
                ->setParameter('idUsuario', $idUsuario),
            $dataLimite
        )
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function calcularPesoMedioAbatidosPorUsuario(int $idUsuario): float
    {
        $resultado = $this->createQueryBuilder('g')
            ->select('AVG(g.peso)')
            ->join('g.fazenda', 'f')
            ->join('f.usuario', 'u')
            ->where('u.id = :idUsuario')
            ->andWhere('g.abatido = true')
            ->setParameter('idUsuario', $idUsuario)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($resultado ?? 0);
    }

    /** @return Gado[] */
    public function buscarUltimosPorUsuario(int $idUsuario, int $limite = 5): array
    {
        return $this->createQueryBuilder('g')
            ->join('g.fazenda', 'f')
            ->join('f.usuario', 'u')
            ->where('u.id = :idUsuario')
            ->setParameter('idUsuario', $idUsuario)
            ->orderBy('g.id', 'DESC')
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();
    }

    public function calcularLeitePerdidoAbatidosPorUsuario(int $idUsuario): float
    {
        $resultado = $this->createQueryBuilder('g')
            ->select('SUM(g.leite)')
            ->join('g.fazenda', 'f')
            ->join('f.usuario', 'u')
            ->where('u.id = :idUsuario')
            ->andWhere('g.abatido = true')
            ->setParameter('idUsuario', $idUsuario)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($resultado ?? 0);
    }

    public function existeGadoVivoPorCodigo(int $codigo, int $idUsuario): bool {
        $result = $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->join('g.fazenda', 'f')
            ->join('f.usuario', 'u')
            ->where('g.codigo = :codigo')
            ->andWhere('u.id = :idUsuario')
            ->andWhere('g.abatido = false')
            ->setParameter('codigo', $codigo)
            ->setParameter('idUsuario', $idUsuario)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }

    ## Querys Para KnpPaginatorBundle

    public function buscarPorUsuarioQuery(int $idUsuario) {
        return $this->createQueryBuilder('g')
            ->join('g.fazenda', 'f')
            ->join('f.usuario', 'u')
            ->where('u.id = :idUsuario')
            ->setParameter('idUsuario', $idUsuario)
            ->orderBy('g.id', 'DESC');
    }

    public function buscarAbatidosEPorUsuarioQuery(int $idUsuario, $search, $fazendaId, $condicao) {
        $dataLimite = (new \DateTime())->modify('-5 years');

        $qb = $this->createQueryBuilder('g')
            ->join('g.fazenda', 'f')
            ->join('f.usuario', 'u')
            ->where('u.id = :idUsuario')
            ->andWhere('g.abatido = true')
            ->setParameter('idUsuario', $idUsuario)
            ->orderBy('g.dataAbate', 'DESC')
            ->addOrderBy('g.id', 'DESC');
        
         if ($search) {
            $qb->andWhere('g.codigo LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($fazendaId) {
            $qb->andWhere('f.id = :fazendaId')
                ->setParameter('fazendaId', $fazendaId);
        }

        if (!$condicao){
            $qb->andWhere('
                (
                    g.nascimento <= :dataLimite
                    OR g.leite < 40
                    OR (g.racao / 7 > 50 AND g.leite < 70)
                    OR (g.peso * 0.5) / 15 >= 18
                )
            ')
            ->setParameter('dataLimite', $dataLimite);
            return $qb;
        }

        if ($condicao === 'LEITE_MENOR_40') {
            $qb->andWhere('g.leite < 40');
        }

        if ($condicao === 'LEITE_RACAO_CRITICO') {
            $qb->andWhere('(g.leite < 70 AND (g.racao / 7) > 50)');
        }

        if ($condicao === 'ARROBA_MAIOR_18') {
            $qb->andWhere('((g.peso * 0.5) / 15) >= 18');
        }

        if ($condicao === 'IDADE_MAIOR_5') {
            $qb->andWhere('g.nascimento <= :dataLimite')
                ->setParameter('dataLimite', $dataLimite);
        }

        return $qb;
    }

    public function buscarPorFazendaQuery(int $idFazenda) {
        return $this->createQueryBuilder('g')
            ->where('g.fazenda = :idFazenda')
            ->setParameter('idFazenda', $idFazenda)
            ->orderBy('g.id', 'DESC');
    }

    public function buscarVivosEPorUsuarioQuery(int $idUsuario, $search, $fazendaId) {
        $qb = $this->createQueryBuilder('g')
            ->join('g.fazenda', 'f')
            ->join('f.usuario', 'u')
            ->where('u.id = :idUsuario')
            ->andWhere('g.abatido = false')
            ->setParameter('idUsuario', $idUsuario)
            ->orderBy('g.id', 'DESC');

        if ($search) {
            $qb->andWhere('g.codigo LIKE :search')
            ->setParameter('search', '%' . $search . '%');
        }

        if ($fazendaId) {
            $qb->andWhere('f.id = :fazendaId')
            ->setParameter('fazendaId', $fazendaId);
        }

        return $qb;
    }

    public function buscarParaAbateQuery(int $idUsuario, $search, $fazendaId, $condicao) {
        $dataLimite = (new \DateTime())->modify('-5 years');

        $qb = $this->createQueryBuilder('g')
            ->join('g.fazenda', 'f')
            ->join('f.usuario', 'u')
            ->where('u.id = :idUsuario')
            ->andWhere('g.abatido = false')
            ->setParameter('idUsuario', $idUsuario)
            ->orderBy('g.id', 'DESC');

        if ($search) {
            $qb->andWhere('g.codigo LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($fazendaId) {
            $qb->andWhere('f.id = :fazendaId')
                ->setParameter('fazendaId', $fazendaId);
        }

        if (!$condicao){
            $qb->andWhere('
                (
                    g.nascimento <= :dataLimite
                    OR g.leite < 40
                    OR (g.racao / 7 > 50 AND g.leite < 70)
                    OR (g.peso * 0.5) / 15 >= 18
                )
            ')
            ->setParameter('dataLimite', $dataLimite);
            return $qb;
        }

        if ($condicao === 'LEITE_MENOR_40') {
            $qb->andWhere('g.leite < 40');
        }

        if ($condicao === 'LEITE_RACAO_CRITICO') {
            $qb->andWhere('(g.leite < 70 AND (g.racao / 7) > 50)');
        }

        if ($condicao === 'ARROBA_MAIOR_18') {
            $qb->andWhere('((g.peso * 0.5) / 15) >= 18');
        }

        if ($condicao === 'IDADE_MAIOR_5') {
            $qb->andWhere('g.nascimento <= :dataLimite')
                ->setParameter('dataLimite', $dataLimite);
        }

        return $qb;
    }

    private function aplicarCondicoesParaAbate(QueryBuilder $queryBuilder, \DateTime $dataLimite): QueryBuilder
    {
        return $queryBuilder
            ->andWhere('
                (
                    g.nascimento <= :dataLimite
                    OR g.leite < 40
                    OR (g.racao / 7 > 50 AND g.leite < 70)
                    OR (g.peso * 0.5) / 15 >= 18
                )
            ')
            ->setParameter('dataLimite', $dataLimite);
    }
}
