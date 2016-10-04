<?php
namespace samples;
if (!(class_exists(__NAMESPACE__ . '\Loader'))) {
    require_once(realpath(__DIR__ . '/loader.php'));
}
use api\AVRSAPI as AVRSAPI;

const PASSPHRASE = 'myTestPassphrase';

// A list of usernames (exising or new) to have API keys created for
$usernames = [
    'testuser1',
    'testuser2', 
    'newApiUser1',
    'newApiUser2',
    'newApiUser3',
];

try {
    // Determine which users already exist, and which need to be created
    echo "Determining which (if any) of the users exist already...".PHP_EOL;
    $existingUsernames = getMatchingUsernames($usernames);
    $usersToCreate = array_diff($usernames, $existingUsernames);
    foreach ($usersToCreate as $idx => $username)
    {
        $userDetails = [];
        $userDetails['username']        = $username;
        $userDetails['active']          = 'T';
        $userDetails['lid']             = '1616';
        $userDetails['cid']             = 11;
        $userDetails['fullname']        = 'Fee Calc Test User '.$idx;
        $userDetails['email']           = 'abc@example.com';
        
        echo 'Creating user "'.$username.'"'.PHP_EOL;
        createUser($userDetails);
    }

    // Now retrieve all desired users
    echo "Retrieving the full user set...".PHP_EOL;
    $users = getUsers($usernames);
    
    // Remove existing API keys for any of these users (constrained to 2, and need secret for each anyway)
    $userIds = [];
    foreach ($users as $idx => $user) {
        $userIds[] = $user['uid'];
    }
    echo "Searching for any existing API keys for the user set...".PHP_EOL;
    $existingKeys = getApiKeysByUserIds($userIds);
    echo 'Found '.count($existingKeys).' existing keys for UserIds ['.implode(',', $userIds).'].'.PHP_EOL;
    if (count($existingKeys) >= 1) {
        foreach ($existingKeys as $idx => $apiKey) {
            $id = $apiKey['id'];
            echo '...Deleting API key with ID of '.$id.'.'.PHP_EOL;     // Note: API does not support IN for deletion
            deleteApiKey($id);
        }
    }

    // Create a new key for each of the users
    foreach ($users as $user) {
        $keyDetails = [];
        $keyDetails['uid']          = $user['uid'];
        $keyDetails['lid']          = $user['lid'];
        $keyDetails['passphrase']   = md5(PASSPHRASE);

        echo 'Generating API key for UID '.$keyDetails['uid'].' / LID '.$keyDetails['lid']. PHP_EOL;
        createApiKey($keyDetails);
    }    
    
    
} catch (\Exception $e) {
    echo "Exception: ".$e->getMessage().PHP_EOL;
    exit(1);
}
exit(0);




/*
 * Creates a user.
 * 
 * @param array $userDetails    The key/value pairs of details/attributes
 * @return array                The API result, as an assoc array
 * @throws Exception            On http error
 */
function createUser(array $userDetails)
{
    $api = new AVRSAPI();
    $api->setMethod('POST');
    $api->setURL('/api/v1.5/users/');
    foreach ($userDetails as $key => $value) {
        $api->addPayload($key, $value);
    }
    $api->send();
    $httpInfo = $api->getInfo();
    $results = json_decode($api->getResult(), true);
    if ($httpInfo['http_code'] != 200) {
        print_r($results);
        throw new \Exception('Unable to create user '.$userDetails['username'].'.  Received http error code: '.$httpInfo['http_code']);
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
function getUsers(array $usernames)
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
    $api = new AVRSAPI();
    $api->setMethod('GET');
    $api->setURL('/api/v1.5/users/?username='.$apiJsonPredicateString);
    $api->send();
    $httpInfo = $api->getInfo();
    $results = json_decode($api->getResult(), true);
    if ($httpInfo['http_code'] != 200) {
        print_r($results);
        throw new \Exception('Unable to retrieve existing user list.  Received http error code: '.$httpInfo['http_code']);
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
function getMatchingUsernames(array $usernames)
{
    $users = getUsers($usernames);

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
function getApiKeysByUserIds(array $userIds)
{
    if (count($userIds) == 0) {
        return [];
    }

    $apiJsonPredicateString = '{"_in":['.implode(',', $userIds).']}';

    // Query AVRS
    $api = new AVRSAPI();
    $api->setMethod('GET');
    $api->setURL('/api/v1.5/apiauthkeys/?uid='.$apiJsonPredicateString);
    $api->send();
    $httpInfo = $api->getInfo();
    $results = json_decode($api->getResult(), true);
    if ($httpInfo['http_code'] != 200) {
        print_r($results);
        throw new \Exception('Unable to retrieve existing APIKey list.  Received http error code: '.$httpInfo['http_code']);
    }

    if (is_array($results) && isset($results['keys']) && is_array($results['keys'])) {
        return $results['keys'];
    }
    return [];
}


/*
 * Delete an existing API key.
 * 
 * @param int $keyId        The ID of the APIKey to delete.
 * @throws Exception        On http error
 */
function deleteApiKey($keyId)
{
    // Query AVRS
    $api = new AVRSAPI();
    $api->setMethod('DELETE');
    $api->setURL('/api/v1.5/apiauthkeys/?id='.$keyId);
    $api->send();
    $httpInfo = $api->getInfo();
    $results = json_decode($api->getResult(), true);
    if ($httpInfo['http_code'] != 200) {
        print_r($results);
        throw new \Exception('Unable to delete APIKey with ID of '.$keyId.'.  Received http error code: '.$httpInfo['http_code']);
    }
}


/*
 * Creates an API key.
 * 
 * @param array $keyDetails     The key/value pairs of details/attributes
 * @return array                The API key, as an assoc array
 * @throws Exception            On http error
 */
function createApiKey(array $keyDetails)
{
    $api = new AVRSAPI();
    $api->setMethod('POST');
    $api->setURL('/api/v1.5/apiauthkeys/');
    foreach ($keyDetails as $key => $value) {
        $api->addPayload($key, $value);
    }
    $api->send();
    $httpInfo = $api->getInfo();
    $results = json_decode($api->getResult(), true);
    if ($httpInfo['http_code'] != 200) {
        print_r($results);
        throw new \Exception('Unable to create API key.  Received http error code: '.$httpInfo['http_code']);
    }
    if (isset($results['keys'][0]) && is_array($results['keys'][0])) {
        return $results['keys'][0];
    }
    return [];
}
