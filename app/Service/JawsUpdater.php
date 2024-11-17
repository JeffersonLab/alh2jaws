<?php

namespace App\Service;

use App\Model\JawsActionsList;
use App\Model\LocationsList;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;


class JawsUpdater
{

    //TODO fetch from https://ace.jlab.org/jaws/ajax/list-priorities
    const PRIORITIES = [
        'P1_CRITICAL' => 1,
        'P2_MAJOR' => 2,
        'P3_MINOR' => 3,
        'P4_INCIDENTAL' => 4,
    ];

    //TODO fetch from https://ace.jlab.org/jaws/ajax/list-systems
    const SYSTEMS = [
        'Aperture' => '1',
        'BCM' => '2',
        'BELS' => '3',
        'BLM' => '4',
        'Beam Dump' => '5',
        'Box PS' => '6',
        'BPM' => '7',
        'CAMAC' => '8',
        'Crate' => '9',
        'Cryo' => '10',
        'Power Meter' => '11',
        'Gun' => '12',
        'Harp' => '13',
        'Helicity' => '14',
        'IOC' => '15',
        'Ion Chamber' => '16',
        'LCW' => '17',
        'Laser' => '18',
        'MO' => '19',
        'Misc' => '20',
        'ODH' => '21',
        'RF' => '22',
        'RF Separators' => '23',
        'RadCon' => '24',
        'Trim' => '25',
        'Trim Rack' => '26',
        'Vacuum Pump' => '27',
        'Warm RF' => '28',
        'Vacuum Valve' => '30',
        'Temperature' => '31',
        'NDX' => '32',
    ];

    //Stores the keycloak Access Token
    protected $accessToken;
    // Collection of actions defined in Jaws
    protected  Collection $jawsActions;
    protected  Collection $locations;

    public function __construct()
    {
        $jawsActionsList = new JawsActionsList();
        $locationsList = new LocationsList();

        $this->jawsActions = $jawsActionsList->actions();
        $this->locations = $locationsList->locations();

        $this->fetchAccessToken();
    }

    protected function fetchAccessToken()
    {
        // https://andrefigueira.com/2017/01/13/php-league-oauth-2-0-password-grant-usage
        // http://www.inanzzz.com/index.php/post/l4zx/creating-a-symfony-oauth2-api-client-that-authenticates-with-password-grant-type
        $client = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId' => config('settings.client_id'),
            'clientSecret' => null,
            'redirectUri' => null,
            'urlAuthorize' => null,
            'urlAccessToken' => config('settings.access_token_url'),
            'urlResourceOwnerDetails' => null,
        ]);

        $this->accessToken = $client->getAccessToken('password', [
            'username' => config('settings.username'),
            'password' => config('settings.password'),
        ]);
    }

    public function authorizationBearer()
    {
        return 'Authorization: bearer ' . $this->accessToken->getToken();
    }

    /**
     * Converts a string priority name into a priorityId integer
     */
    public function priorityId(string $priority){
        return self::PRIORITIES[$priority];
    }

    /**
     * Converts a string priority name into a priorityId integer
     */
    public function systemId(string $name){
        return self::SYSTEMS[$name];
    }

    /**
     * converts a string action name into an actionId integer
     */
    public function actionId(string $name){
        $action = (object) $this->jawsActions->where('name',$name)->first();
        return isset($action->id) ? $action->id : null;
    }

    public function locationId(string $name){
        $location = (object) $this->locations->where('name',$name)->first();
        return isset($location->id) ? $location->id : null;
    }

    public function locationIdArray(array $names){
        return array_map([$this, 'locationId'], $names);
    }

    /**
     * Executes the remote add-action action
     */
    public function addAction(object $action): void
    {
        $this->executeCommand('add-action', [
            'name' => $action->name,
            'systemId' => $this->systemId($action->category),
            'priorityId' => $this->priorityId($action->priority),
            'correctiveAction' => $action->correctiveaction,
            'rationale' => $action->rationale,
            'filterable' => $action->filterable ? 'Y' : 'N',
            'latchable' => $action->latching ? 'Y' : 'N',
            'onDelaySeconds' => $action->ondelayseconds,
            'offDelaySeconds' => $action->offdelayseconds,
        ]);
    }

    /**
     * Executes the remote remove-action action
     */
    public function removeAction(object $action): void
    {
        $this->executeCommand('remove-action', [
            'id' => $action->id,
        ]);
    }


    /**
     * Executes the remote add-alarm action
     */
    public function addAlarm(object $alarm){
        $this->executeCommand('add-alarm', [
            'name' => $alarm->name,
            'pv' => $alarm->pv,
            'actionId' => $this->actionId($alarm->action),
            'locationId[]' => implode(',', $this->locationIdArray($alarm->location)),
            'screenCommand' => $alarm->screencommand,
            'managedBy' => $alarm->managedby,
            'maskedBy' => $alarm->maskedby,
        ]);
    }


    /**
     * Executes the remote remove-alarm action
     */
    public function removeAlarm(object $alarm): void
    {
        $this->executeCommand('remove-alarm', [
            'id' => $alarm->id,
        ]);
    }


    protected function httpQuery(array $params){
        $query = http_build_query($params);
        // Below we unescape the [] that the locationId parameter requires
        return str_replace('%5B0%5D','[]', $query);
    }

    /**
     * @throws Exception
     *
     * @todo Use Guzzle?
     */
    protected function executeCommand(string $command, array $params)
    {
//        dd($params);
        $commandURL = sprintf('%s/%s', Config::get('settings.jaws_api_base'), $command);
        Config::set('app.debug',true);
        if (Config::get('app.debug') || true) {
            $cmd = sprintf("%s -s -S -X POST --data '%s' -H '%s' %s 2>&1",
                Config::get('hco.curl'), $this->httpQuery($params),
                $this->authorizationBearer(), $commandURL);
            //dd('Executing curl equivalent of command ', [$cmd]);
            var_dump($cmd);

        }

        //dd($commandURL);
        $ch = curl_init($commandURL);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        //curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieJar);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [$this->authorizationBearer()]);

        $returned = curl_exec($ch);

        if ($returned === false) {
            throw new Exception('curl_exec returned FALSE');
        }

        $json = json_decode($returned);

        if ($json->stat != 'ok') {
            if (isset($json->error)) {
                $error = $json->error;
            } else {
                $error = 'Unable to execute command';
            }

            throw new Exception($error);
        }
    }
}

//class
