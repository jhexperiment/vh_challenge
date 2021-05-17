<?php

namespace App\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository;
use App\Entity\UserSetting;
use App\Entity\User;


class UserController extends AbstractController
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    private function parseFields($data) {

      $fields = [];
      if (array_key_exists('fields', $data) && is_array($data['fields'])) {

        foreach ($data['fields'] as $name => $value) {

          if (User::isValidField($name)) {

            if ($name === 'user_settings') {

              if (is_array($value)) {

                foreach ($value as $settingType => $settingValue) {

                  if (UserSetting::isValidType($settingType)) {

                    if (!array_key_exists('user_settings', $fields)) {
                      $fields['user_settings'] = [];
                    }

                    $fields['user_settings'][$settingType] = true;
                  }
                }
              }
              else {
                $fields['user_settings'] = UserSetting::$validTypes;
              }
            }
            else {
              $fields[$name] = true;
            }
          }
        }
      }
      else {
        $fields = User::$validFields;
        $fields['user_settings'] = UserSetting::$validTypes;
      }

      return $fields;
    }

    private function parseUserSettings($data, &$userData) {

      if (array_key_exists('user_settings', $data) && is_array($data['user_settings'])) {

        $userData['user_settings'] = [];

        foreach($data['user_settings'] as $type => $value) {

          if (UserSetting::isValidType($type)) {

            if (array_key_exists($type, $data['user_settings'])) {
              $userData['user_settings'][$type] = [];
            }

            if (is_array($value)) {

              foreach($value as $arrValue) {

                if (
                  is_array($arrValue)
                  && (
                    array_key_exists('value', $arrValue)
                    || array_key_exists('delete', $arrValue)
                  )
                ) {

                  if (array_key_exists('id', $arrValue)) {
                    $userSetting['id'] = $arrValue['id'];
                  }
                  if (array_key_exists('value', $arrValue)) {
                    $userSetting['value'] = $arrValue['value'];
                  }
                  if (array_key_exists('delete', $arrValue)) {
                    $userSetting['delete'] = $arrValue['delete'];
                  }

                  $userData['user_settings'][$type][] = $userSetting;
                }
                else if (is_string($arrValue)) {
                  $userData['user_settings'][$type][] = $arrValue;
                }
              }
            }
            else if (is_string($value)) {
              $userData['user_settings'][$type][] = [
                'value' => $value
              ];
            }
          }
        }
      }
    }

    /**
     * @Route("/users", name="get_users", methods={"GET"})
     */
    public function retrieveMultiple(Request $request): Response
    {
      try {
        $data = json_decode($request->getContent(), true);

        $fields = $this->parseFields($data);
        $limit = 10;
        if (array_key_exists('limit', $data) && is_int($data['limit']) && $data['limit'] > 0) {
          $limit = $data['limit'];
        }
        $offset = 0;
        if (array_key_exists('offset', $data) && is_int($data['offset'])) {
          $offset = $data['offset'];
        }

        $users = $this->userRepository->getUsersArray($fields, $limit, $offset);

        return $this->json([
          'data' => $data,
          'fields' => $fields,
          'users' => $users
        ], Response::HTTP_OK);
      }
      catch (NotFoundHttpException $e) {
        return $this->json([
          'message' => $e->getMessage(),
          'code' => $e->getCode()
        ], Response::HTTP_NOT_FOUND);
      }
    }

    /**
     * @Route("/users", name="create_user", methods={"POST"})
     */
    public function create(Request $request): Response
    {
        try {

          $data = json_decode($request->getContent(), true);

          $userData = [];

          if (empty($data['name']) || empty($data['email'])) {
              throw new NotFoundHttpException('Expecting mandatory parameters!');
          }

          $userData['name'] = $data['name'];
          $userData['email'] = $data['email'];

          $isExistingUser = $this->userRepository->isExistingUserEmail($userData['email']);

          if ($isExistingUser) {
            /* Arbitrairy exception code 22 being used for email already existing so not to
               blatenely let spammers detect user accounts via email.
            */
            throw new NotFoundHttpException('Invalid user.', null, 22);
          }

          $userData['active_status'] = empty($data['active_status']) || !is_int($data['active_status']) ? 1 : $data['active_status'];

          $this->parseUserSettings($data, $userData);

          $userId = $this->userRepository->saveUser($userData);

          return $this->json([
            'user_id' => $userId,
            'data' => $data,
            'userData' => $userData
          ], Response::HTTP_CREATED);
        }
        catch (NotFoundHttpException $e) {
          return $this->json([
            'message' => $e->getMessage(),
            'code' => $e->getCode()
          ], Response::HTTP_NOT_ACCEPTABLE);
        }
    }

    /**
     * @Route("/users", name="create_multiple_user", methods={"PUT"})
     */
    public function updateMultiple(Request $request): Response
    {
      $response = new Response();
      $response->setStatusCode(Response::HTTP_FORBIDDEN);
      return $response;
    }

    /**
     * @Route("/users", name="delete_multiple_user", methods={"DELETE"})
     */
    public function deleteMultiple(Request $request): Response
    {
      $response = new Response();
      $response->setStatusCode(Response::HTTP_FORBIDDEN);
      return $response;
    }

    /**
     * @Route("/users/{id}", name="get_user", methods={"GET"})
     */
    public function retrieve(Request $request, int $id): Response
    {
      try {
        $data = json_decode($request->getContent(), true);

        $fields = $this->parseFields($data);

        $user = $this->userRepository->getUserArray($id, $fields);

        return $this->json($user, Response::HTTP_OK);
      }
      catch (NotFoundHttpException $e) {
        return $this->json([
          'message' => $e->getMessage(),
          'code' => $e->getCode()
        ], Response::HTTP_NOT_FOUND);
      }
    }

    /**
     * @Route("/users/{id}", name="create_user_with_id", methods={"POST"})
     */
    public function createWithId(Request $request, int $id): Response
    {
      $response = new Response();
      $response->setStatusCode(Response::HTTP_FORBIDDEN);
      return $response;
    }

    /**
     * @Route("/users/{id}", name="update_user", methods={"PUT"})
     */
    public function update(Request $request, int $id): Response
    {
        try {
          $data = json_decode($request->getContent(), true);

          $userData = [];
          if (array_key_exists('name', $data)) {
            $userData['name'] = $data['name'];
          }

          if (array_key_exists('email', $data)) {
            $userData['email'] = $data['email'];
          }

          if (array_key_exists('active_status', $data) && is_int($data['active_status'])) {
            $userData['active_status'] = $data['active_status'];
          }

          $this->parseUserSettings($data, $userData);

          $user = $this->userRepository->modifyUser($id, $userData);

          if (!$user) {
            throw new NotFoundHttpException('Invalid user.');
          }

          return $this->json([
            'id' => $id,
            'data' => $data,
            'userData' => $userData,
            'user' => $user
          ], 200);
        }
        catch (NotFoundHttpException $e) {
          return $this->json([
            'message' => $e->getMessage(),
            'code' => $e->getCode()
          ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * @Route("/users/{id}", name="delete_user", methods={"DELETE"})
     */
    public function delete(int $id): Response
    {
        try {

          $user = $this->userRepository->deleteUser($id);

          if (!$user) {
            throw new NotFoundHttpException('Not found.');
          }

          $response = new Response();
          $response->setStatusCode(Response::HTTP_NO_CONTENT);
          return $response;
        }
        catch (NotFoundHttpException $e) {
          return $this->json([
            'message' => $e->getMessage(),
            'code' => $e->getCode()
          ], Response::HTTP_NOT_FOUND);
        }
    }
}
