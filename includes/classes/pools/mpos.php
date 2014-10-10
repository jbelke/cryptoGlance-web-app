<?php
require_once('abstract.php');
/*
 * @author Stoyvo
 */
class Pools_Mpos extends Pools_Abstract {

    // Pool Information
    protected $_apiKey;
    protected $_userId;
    protected $_type = 'mpos';
    
    // api calls to make
    protected $_actions = array(
        'public',
        'getpoolstatus',
        'getblockstats',
        'getuserbalance',
        'getuserstatus',
    );

    public function __construct($params) {
        parent::__construct($params);
        $this->_apiKey = $params['apikey'];
        $this->_userId = $params['userid'];
        $this->_fileHandler = new FileHandler('pools/' . $this->_type . '/'. hash('md4', $params['apikey']) .'.json');
    }

    public function update() {
        if ($GLOBALS['cached'] == false || $this->_fileHandler->lastTimeModified() >= 30) { // updates every 30 seconds
            $poolData = array();
            foreach ($this->_actions as $action) {
                $poolData[$action] = curlCall($this->_apiURL  . '/index.php?page=api&id='. $this->_userId .'&api_key='. $this->_apiKey . '&action=' . $action);
                
                if ($action == 'getpoolstatus') {
                    $poolData[$action] = $poolData[$action]['getpoolstatus']['data'];
                } else if ($action == 'getblockstats') {
                    $poolData[$action] = $poolData[$action]['getblockstats']['data'];
                } else if ($action == 'getuserbalance') {
                    $poolData[$action] = $poolData[$action]['getuserbalance']['data'];
                } else if ($action == 'getuserstatus') {
                    $poolData[$action] = $poolData[$action]['getuserstatus']['data'];
                }
            }
            
            // Data Order
            $data['type'] = $this->_type;
            
            $data['balance'] = $poolData['getuserbalance']['confirmed'];
            $data['unconfirmed_balance'] = $poolData['getuserbalance']['unconfirmed'];
            
            $data['network_hashrate'] = formatHashrate($poolData['getpoolstatus']['nethashrate']);

            $data['pool_hashrate'] = formatHashrate($poolData['getpoolstatus']['hashrate']);
            
            $data['user_hashrate'] = formatHashrate($poolData['getuserstatus']['hashrate']);
            
            $data['pool_workers'] = $poolData['getpoolstatus']['workers'];
            $data['efficiency'] = $poolData['getpoolstatus']['efficiency'] . '%';
            $data['accepted'] = $poolData['public']['shares_this_round'];
            $data['rejected'] = round($poolData['public']['shares_this_round'] - ($poolData['public']['shares_this_round'] * ($poolData['getpoolstatus']['efficiency']/100)));
            $data['difficulty'] = round($poolData['getpoolstatus']['networkdiff'], 5);
            
            $timeSinceLastBlock = formatTimeElapsed($poolData['getpoolstatus']['timesincelast']);
            
            $data['time_since_last_block'] = $timeSinceLastBlock;
            $data['%_of_expected'] = round(($poolData['public']['shares_this_round'] / $poolData['getpoolstatus']['estshares']) * 100, 2) . '%';
            $data['current_block'] = $poolData['getpoolstatus']['currentnetworkblock'];
            $data['last_block'] = $poolData['getpoolstatus']['lastblock'];
            $data['blocks_pool_found'] = $poolData['getblockstats']['TotalValid'];

            $data['username'] = $poolData['getuserstatus']['username'];
            
            $data['url'] = $this->_apiURL;
            
            $this->_fileHandler->write(json_encode($data));
            return $data;
        }
        
        return json_decode($this->_fileHandler->read(), true);
    }

}
