<?php
namespace PaulJulio\AvrsApi\Examples;

use \Exception;

class UserCreation extends AbstractExample {

    const PASSPHRASE = 'myTestPassphrase';

    public function run() {

        // A list of usernames (exising or new) to have API keys created for
        $usernames = [
            'testuser1',
            'testuser2',
            'newApiUser1',
            'newApiUser2',
            'newApiUser3',
        ];

        // Determine which users already exist, and which need to be created
        $existingUsernames = $this->getMatchingUsernames($usernames);
        $usersToCreate = array_diff($usernames, $existingUsernames);
        foreach ($usersToCreate as $idx => $username) {
            $userDetails = [
                'username' => $username,
                'active' => 'T',
                'lid' => '1616',
                'cid' => 11,
                'fullname' => 'Fee Calc Test User ' . $idx,
                'email' => 'abc@example.com',
            ];

            $this->createUser($userDetails);
        }

        // Now retrieve all desired users
        $users = $this->getUsers($usernames);

        // Each user can have at most 2 keys.  If 2 already, delete the oldest.
        $this->cleanupApiKeysForUsers($users);

        // Create a new key for each of the users
        foreach ($users as $user) {
            $keyDetails = [
                'uid' => $user['uid'],
                'lid' => $user['lid'],
                'passphrase' => md5(PASSPHRASE),
            ];

            $this->createApiKey($keyDetails);
        }
    }


        /*
         * Creates a user.
         *
         * @param array $userDetails    The key/value pairs of details/attributes
         * @return array                The API result, as an assoc array
         * @throws Exception            On http error
         */
        private function createUser(array $userDetails)
        {
            $this->resetApi();
            $this->api->setMethod('POST');
            $this->api->setURL('/api/v1.5/users/');
            foreach ($userDetails as $key => $value) {
                $this->api->addPayload($key, $value);
            }
            $this->send();
            $httpInfo = $this->api->getInfo();
            $results = json_decode($this->api->getResult(), true);
            if ($httpInfo['http_code'] != 200) {
                error_log($results);
                throw new Exception('Unable to create user '.$userDetails['username'].'.  Received http error code: '.$httpInfo['http_code']);
            }
            return $results;
        }


        /*
         * Retrieve a set of users.
         *
         * @param array $usernames  The usernames to retrieve.
         * @return array            A set of user entities
         * @throws Exception        On http error
         */
        private function getUsers(array $usernames)
        {
            if (count($usernames) == 0) {
                return [];
            }

            // Add quotes for safety around each username, and implode the array using ',' as glue
            $_usernames = [];
            foreach ($usernames as $un) {
                $_usernames[] = '"'.$un.'"';
            }
            $apiJsonPredicateString = '{"_in":['.implode(',', $_usernames).']}';

            // Query AVRS
            $this->resetApi();
            $this->api->setMethod('GET');
            $this->api->setURL('/api/v1.5/users/?username='.$apiJsonPredicateString);
            $this->send();
            $httpInfo = $thi->api->getInfo();
            $results = json_decode($thi->api->getResult(), true);
            if ($httpInfo['http_code'] != 200) {
                error_log($results);
                throw new Exception('Unable to retrieve existing user list.  Received http error code: '.$httpInfo['http_code']);
            }

            if (is_array($results) && isset($results['users']) && is_array($results['users'])) {
                return $results['users'];
            }
            return [];
        }


        /*
         * Return an array of any existing usernames that match the desired input set.
         *
         * @param array $usernames  Array of usernames to match against
         * @return array            The set of usernames that matched (already exist)
         * @throws Exception        On http error
         */
        private function getMatchingUsernames(array $usernames)
        {
            $users = $this->getUsers($usernames);

            $matches = [];
            foreach ($users as $user) {
                $matches[] = $user['username'];
            }
            return $matches;
        }


        /*
         * Retrieve a set of API keys.
         *
         * @param array $userIds    The userIds of the API keys.
         * @return array            A set of APIKey entities
         * @throws Exception        On http error
         */
        private function getApiKeysByUserIds(array $userIds)
        {
            if (count($userIds) == 0) {
                return [];
            }

            $apiJsonPredicateString = '{"_in":['.implode(',', $userIds).']}';

            // Query AVRS
            $this->resetApi();
            $this->api->setMethod('GET');
            $this->api->setURL('/api/v1.5/apiauthkeys/?uid='.$apiJsonPredicateString);
            $this->send();
            $httpInfo = $this->api->getInfo();
            $results = json_decode($this->api->getResult(), true);
            if ($httpInfo['http_code'] != 200) {
                error_log($results);
                throw new Exception('Unable to retrieve existing APIKey list.  Received http error code: '.$httpInfo['http_code']);
            }

            if (is_array($results) && isset($results['keys']) && is_array($results['keys'])) {
                return $results['keys'];
            }
            return [];
        }


        /**
         * Each user can have a maximum of 2 keys (currently) per environment.
         * If a user has two keys, delete the oldest to make room for a new key.
         * Array { [userId] => {
         *              [count] => number of keys
         *              [earliest-key-id] => key id
         *              [earliest-creation-time] => timestamp of earliest recorded creation time
         *
         * @param $users
         */
        private function cleanupApiKeysForUsers(array $users)
        {
            $userIds = [];
            foreach ($users as $idx => $user) {
                $userIds[] = $user['uid'];
            }
            $existingKeys = $this->getApiKeysByUserIds($userIds);
            error_log('Found '.count($existingKeys).' existing keys for UserIds ['.implode(',', $userIds).'].'.PHP_EOL);

            $keyData = [];
            foreach ($existingKeys as $apiKeyDetails) {
                $userId = $apiKeyDetails['uid'];
                $keyId = $apiKeyDetails['id'];
                $compareTime = strtotime($apiKeyDetails['create-time']);
                // First key encountered
                if (!isset($keyData[$userId])) {
                    $keyData[$userId] = [
                        'count' => 1,
                        'earliest-key-id' => $keyId,
                        'earliest-creation-time' => $compareTime,
                    ];
                }
                // Successive key encountered
                else {
                    $keyData[$userId]['count']++;
                    if ($compareTime < $keyData[$userId]['earliest-creation-time']) {
                        $keyData[$userId]['earliest-key-id'] = $keyId;
                        $keyData[$userId]['earliest-creation-time'] = $compareTime;
                    }
                }
            }
            foreach ($keyData as $uid => $details) {
                if ($details['count'] >= 2) {
                    // Note: API does not support predicates for deletion.  Keys must be deleted singularly.
                    error_log('...Deleting API key with UID='.$uid.' and KeyID='.$details['earliest-key-id'].'.'.PHP_EOL);
                    $this->deleteApiKey($details['earliest-key-id']);
                }
            }
        }


        /*
         * Delete an existing API key.
         *
         * @param int $keyId        The ID of the APIKey to delete.
         * @throws Exception        On http error
         */
        private function deleteApiKey($keyId)
        {
            // Query AVRS
            $this->resetApi();
            $this->api->setMethod('DELETE');
            $this->api->setURL('/api/v1.5/apiauthkeys/?id='.$keyId);
            $this->send();
            $httpInfo = $this->api->getInfo();
            $results = json_decode($this->api->getResult(), true);
            if ($httpInfo['http_code'] != 200) {
                error_log($results);
                throw new Exception('Unable to delete APIKey with ID of '.$keyId.'.  Received http error code: '.$httpInfo['http_code']);
            }
        }


        /*
         * Creates an API key.
         *
         * @param array $keyDetails     The key/value pairs of details/attributes
         * @return array                The API key, as an assoc array
         * @throws Exception            On http error
         */
        private function createApiKey(array $keyDetails)
        {
            $this->resetApi();
            $this->api->setMethod('POST');
            $this->api->setURL('/api/v1.5/apiauthkeys/');
            foreach ($keyDetails as $key => $value) {
                $this->api->addPayload($key, $value);
            }
            $this->send();
            $httpInfo = $this->api->getInfo();
            $results = json_decode($this->api->getResult(), true);
            if ($httpInfo['http_code'] != 200) {
                error_log($results);
                throw new Exception('Unable to create API key.  Received http error code: '.$httpInfo['http_code']);
            }
            if (isset($results['keys'][0]) && is_array($results['keys'][0])) {
                return $results['keys'][0];
            }
            return [];
        }
}

