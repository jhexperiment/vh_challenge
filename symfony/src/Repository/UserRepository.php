<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use DateTime;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    private $manager;
    private $userSettingsRepository;

    public function __construct(
      ManagerRegistry $registry,
      EntityManagerInterface $manager,
      UserSettingRepository $userSettingsRepository
    ){
        parent::__construct($registry, User::class);
        $this->manager = $manager;
        $this->userSettingsRepository = $userSettingsRepository;
    }

    public function getUser($id) {
      return $this->find($id);
    }

    private function parseFields($fields) {
      if (empty($fields) || !is_array($fields)) {
        $fields = User::$validFields;
        $fields['user_settings'] = UserSetting::$validTypes;
      }

      $userFields = ['u.id'];
      $userSettingTypes = [];
      foreach ($fields as $name => $value) {
        if (User::isValidField($name)) {
          if ($name === 'user_settings') {
            foreach ($value as $type => $settingValue) {
              if (UserSetting::isValidType($type)) {
                $userSettingTypes[] = $type;
              }
            }
          }
          else if ($value === true) {
            $userFields[] = "u.{$name}";
          }
        }
      }

      return [
        'userFields' => $userFields,
        'userSettingTypes' => $userSettingTypes
      ];
    }

    public function getUserArray($id, $fields)
    {

      $parsedFields = $this->parseFields($fields);

      $user = $this->createQueryBuilder('u')
                  ->select($parsedFields['userFields'])
                  ->andWhere('u.id = :userId')
                  ->setParameter('userId', $id)
                  ->getQuery()
                  ->getOneOrNullResult();

      if (!empty($user)) {

        if (array_key_exists('created_date', $user)) {
          $user['created_date'] = $user['created_date']->getTimestamp();
        }

        if (array_key_exists('updated_date', $user)) {
          $user['updated_date'] = $user['updated_date']->getTimestamp();
        }

        if (count($parsedFields['userSettingTypes']) > 0) {
          $user['user_settings'] = $this->userSettingsRepository->getUserSettingsArray($id, $parsedFields['userSettingTypes']);
        }

      }

      return $user;
    }

    public function getUsersArray($fields, $limit, $offset)
    {

      $parsedFields = $this->parseFields($fields);

      $userResults = $this->createQueryBuilder('u')
                  ->select($parsedFields['userFields'])
                  ->getQuery()
                  ->setMaxResults($limit)
                  ->setFirstResult($offset)
                  ->getResult();

      $users = [];
      foreach ($userResults as $userResult) {

        if (array_key_exists('created_date', $userResult)) {
          $userResult['created_date'] = $userResult['created_date']->getTimestamp();
        }

        if (array_key_exists('updated_date', $userResult)) {
          $userResult['updated_date'] = $userResult['updated_date']->getTimestamp();
        }

        $users[$userResult['id']] = $userResult;
      }

      $userSettingResults = null;
      if (count($parsedFields['userSettingTypes']) > 0) {
        $userSettingResults = $this->userSettingsRepository->getUsersSettingsArray(array_keys($users), $parsedFields['userSettingTypes']);
      }

      if (is_array($userSettingResults) && count($userSettingResults) > 0) {

        foreach($userSettingResults as $userSettingResult) {
          $userId = $userSettingResult['user_id'];

          if (array_key_exists($userId, $users)) {

            if (!array_key_exists('user_settings', $users[$userId])) {
              $users[$userId]['user_settings'] = [];
            }

            $type = $userSettingResult['type'];
            if (!array_key_exists($type, $users[$userId]['user_settings'])) {
              $users[$userId]['user_settings'][$type] = [];
            }

            $users[$userId]['user_settings'][$type][$userSettingResult['id']] = $userSettingResult['value'];

          }
        }
      }

      return $users;
    }

    public function saveUser($userData)
    {
      $timestamp = new DateTime('NOW');

      $newUser = new User();
      $newUser
        ->setName($userData['name'])
        ->setEmail($userData['email'])
        ->setActiveStatus($userData['active_status'])
        ->setCreatedDate($timestamp)
        ->setUpdatedDate($timestamp);

      if ($userData['user_settings'] && is_array($userData['user_settings'])) {
        foreach($userData['user_settings'] as $type => $arr) {
          if (UserSetting::isValidType($type)) {

            if (is_array($arr)) {
              foreach($arr as $value) {
                if (is_array($value) && array_key_exists('value', $value)) {
                  $userSetting = new UserSetting();
                  $userSetting
                    ->setType($type)
                    ->setValue($value['value']);
                  $this->manager->persist($userSetting);
                  $newUser->addUserSetting($userSetting);
                }
                else if (is_string($value)) {
                  $userSetting = new UserSetting();
                  $userSetting
                    ->setType($type)
                    ->setValue($value);
                  $this->manager->persist($userSetting);
                  $newUser->addUserSetting($userSetting);
                }
              }
            }
          }
        }
      }

      $this->manager->persist($newUser);
      $this->manager->flush();

      return $newUser->getId();
    }

    public function modifyUser($id, $userData)
    {
      $timestamp = new DateTime('NOW');

      $user = $this->find($id);

      if (!$user) {
        // no user found with id
        return null;
      }

      $user->setUpdatedDate($timestamp);

      if (array_key_exists('email', $userData) && $user->getEmail() !== $userData['email']) {
        // email does not match email from user with given ID

        $userWithEmail = $this->createQueryBuilder('u')
            ->andWhere('u.email = :val')
            ->setParameter('val', $userData['email'])
            ->getQuery()
            ->getOneOrNullResult();

        if ($userWithEmail) {
          // user with email exists already, cannot update requested user
          // with ID to this email
          return null;
        }

        // no user with email exists yet, available to update existing user
        $user->setEmail($userData['email']);
      }

      if (array_key_exists('name', $userData) && $user->getName() !== $userData['name']) {
        $user->setName($userData['name']);
      }


      if (array_key_exists('active_status', $userData) && User::isValueActiveStatus($userData['active_status'])) {
        $user->setActiveStatus($userData['active_status']);
      }

      $numDeleted = null;
      $userSettingsToUpdate = null;
      $userSettingIdsToDelete = null;
      $inArray = false;

      if (array_key_exists('user_settings', $userData)) {
        $userSettingsToUpdate = [];
        $userSettingIdsToDelete = [];
        foreach ($userData['user_settings'] as $type => $arr) {

          foreach ($arr as $requestedUserSetting) {

            if (array_key_exists('delete', $requestedUserSetting)) {
              $inArray = true;
              // delete user setting
              if (array_key_exists('id', $requestedUserSetting)) {
                // delete only if id exists, ignore otherwise
                $userSettingIdsToDelete[] = $requestedUserSetting['id'];
              }
            }
            else if (array_key_exists('id', $requestedUserSetting) && array_key_exists('value', $requestedUserSetting)) {

              // update existing user settting
              $userSettingsToUpdate[$requestedUserSetting['id']] = $requestedUserSetting['value'];
            }
            else if (array_key_exists('value', $requestedUserSetting)){
              // add new user setting
              $userSetting = new UserSetting();
              $userSetting
                ->setType($type)
                ->setValue($requestedUserSetting['value']);
              $this->manager->persist($userSetting);
              $user->addUserSetting($userSetting);
            }
          }
        }

        if (count($userSettingsToUpdate) > 0) {
          $userSettingIds = array_keys($userSettingsToUpdate);
          $userSettings = $this->userSettingsRepository->findById($userSettingIds);
          foreach($userSettings as $userSetting) {
            $userSetting->setValue($userSettingsToUpdate[$userSetting->getId()]);
          }
        }

        if (count($userSettingIdsToDelete) > 0) {
          $numDeleted = $this->userSettingsRepository->bulkDelete($userSettingIdsToDelete);
        }
      }

      $this->manager->flush();

      return [
        'user' => $user,
        'userSettingsToUpdate' => $userSettingsToUpdate,
        'userSettingIdsToDelete' => $userSettingIdsToDelete,
        'numDeleted' => $numDeleted,
        'inArray' => $inArray
      ];

    }

    public function deleteUser($id)
    {
      $user = $this->find($id);

      if ($user) {
        $this->manager->remove($user);
        $this->manager->flush();
      }

      return $user;
    }

    public function isExistingUserId($id): ?int
    {
      return (int) $this->createQueryBuilder('u')
          ->select('count(u.id)')
          ->andWhere('u.id = :val')
          ->setParameter('val', $id)
          ->getQuery()
          ->getSingleScalarResult();
    }

    public function isExistingUserEmail($email): ?int
    {
      return (int) $this->createQueryBuilder('u')
          ->select('count(u.id)')
          ->andWhere('u.email = :val')
          ->setParameter('val', $email)
          ->getQuery()
          ->getSingleScalarResult();
    }

    public function findOneByEmail($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult();
    }

}
