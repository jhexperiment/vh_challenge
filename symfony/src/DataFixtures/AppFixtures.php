<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use App\Entity\User;
use App\Entity\UserSetting;
use DateTime;
use App\Repository\UserRepository;


class AppFixtures extends Fixture
{

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        $timestamp = new DateTime('NOW');

        $settings = [
          'phone_number' => [
            'phoneNumber',
            'tollFreePhoneNumber'
          ],
          'email' => [
            'safeEmail',
            'freeEmail',
            'companyEmail'
          ],
          'address' => [
            'address'
          ],
          'social_media_link' => [
            'instagram',
            'facebook',
            'twitter'
          ]
        ];

        $socialMedia = [
          'instagram' => 'instagram.com/',
          'facebook' => 'facebook.com/',
          'twitter' => 'twitter.com/'
        ];

        for ($i = 0; $i < 50; $i++) {

            $firstName = $faker->firstName;
            $lastName = $faker->lastName;

            $userData = [];
            $userData['name'] = "{$firstName} $lastName";
            $userData['email'] = $faker->email;
            $userData['active_status'] = random_int(0, 1);
            $userData['user_settings'] = [];

            foreach($settings as $type => $props) {

              foreach($props as $prop) {

                if ((bool)random_int(0, 1)) {
                  continue;
                }

                $value = $type === 'social_media_link' ? "{$socialMedia[$prop]}{$lastName}{$firstName}" : $faker->$prop;

                if (array_key_exists($type, $userData['user_settings'])) {
                  $userData['user_settings'][$type] = [];
                }

                $userData['user_settings'][$type][] = $value;
              }
            }

            $userId = $this->userRepository->saveUser($userData);
        }
    }
}
