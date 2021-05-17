<?php

namespace App\Repository;

use App\Entity\UserSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserSetting|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserSetting|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserSetting[]    findAll()
 * @method UserSetting[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSetting::class);
    }

    /**
     * @return UserSetting[] Returns an array of UserSetting objects
     */
    public function findByUserId($id)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.user_id = :val')
            ->setParameter('val', $id)
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function parseTypes($types) {
      $arr = [];
      if (is_string($types)) {
        $arr[] = $types;
      }
      else if (!is_array($types)) {
        return null;
      }
      else if (is_array($types)) {
        foreach ($types as $type) {
          if (is_string($type)) {
            $arr[] = $type;
          }
        }
      }

      return $arr;
    }

    public function getUserSettingsArray($userId, $types)
    {
      $types = $this->parseTypes($types);

      $userSettings = $this->createQueryBuilder('u')
          ->andWhere('u.user = :userId')
          ->andWhere('u.type IN (:types)')
          ->setParameter('userId', $userId)
          ->setParameter('types', $types)
          ->orderBy('u.type', 'ASC')
          ->getQuery()
          ->getResult();

      $userSettingsArr = null;

      if (!empty($userSettings) && count($userSettings) > 0) {
        $userSettingArr = [];
        foreach ($userSettings as $userSetting) {
          if (!array_key_exists($userSetting->getType(), $userSettingArr)) {
            $userSettingArr[$userSetting->getType()] = [];
          }

          $userSettingArr[$userSetting->getType()][$userSetting->getId()] = $userSetting->getValue();
        }
      }

      return $userSettingArr;
    }

    public function getUsersSettingsArray($userIds, $types)
    {
      $types = $this->parseTypes($types);

      $userSettingResults = $this->createQueryBuilder('u')
          ->andWhere('u.user IN (:userIds)')
          ->andWhere('u.type IN (:types)')
          ->setParameter('userIds', $userIds)
          ->setParameter('types', $types)
          ->orderBy('u.type', 'ASC')
          ->getQuery()
          ->getResult();

      $userSettingsArr = [];
      foreach ($userSettingResults as $userSettingResult) {
        $userSettingsArr[] = $userSettingResult->toArray();
      }

      return $userSettingsArr;

      $userSettingsArr = null;

      if (!empty($userSettings) && count($userSettings) > 0) {
        $userSettingArr = [];
        foreach ($userSettings as $userSetting) {
          if (!array_key_exists($userSetting->getType(), $userSettingArr)) {
            $userSettingArr[$userSetting->getType()] = [];
          }

          $userSettingArr[$userSetting->getType()][$userSetting->getId()] = $userSetting->getValue();
        }
      }

      return $userSettingArr;
    }

    public function bulkDelete($idArr) {
      if (!is_array($idArr)) {
        return null;
      }

      $sql = implode(' ', [
        'delete',
        'from App\Entity\UserSetting u',
        'where u.id',
        'in (', implode(',', $idArr), ')'
      ]);

      return $this->getEntityManager()->createQuery($sql)->execute();
    }
}
