<?php
        class auto_switch
        {
			public $api_url = 'http://remote.moneyfromair.com.au/';

			private $home_path = '';
			private $ethos_path = '';

			function __construct()
			{
					// PATH TO SCRIPT
					$this->home_path = dirname(dirname(__FILE__)).'/'; // e.g. /home/ethos/
					$this->ethos_path = '/home/ethos/';

					// Change dir to root folder
					chdir($this->home_path);
			}

			public function run()
			{
				$config_data_json = $this->get_machine_config();
				if(!$config_data_json)
				{
					echo "Error Receiving Data!\r\n";
					return FALSE;
				}
				
				$config_data = json_decode($config_data_json, TRUE);
				if(!isset($config_data['status']) || $config_data['status'] != 'ok')
				{
					echo "Malformed JSON error: $config_data_json\r\n";
					return FALSE;
				}
				
				echo "Received Machine Config: $config_data_json\r\n";
				
				// Switch coin
				file_put_contents($this->ethos_path.'local.conf', $config_data['config']);
				sleep(5);
				
				echo "Stopping miner\r\n";
				shell_exec('/opt/ethos/bin/minestop');
				sleep(5);
				
				echo "Restarting Proxy: $output\r\n";
				$output = shell_exec('/opt/ethos/bin/restart-proxy 2>&1');
				
				if($config_data['run_ethos_overclock'])
				{
					echo "Running ethos-overclock\r\n";
					shell_exec('/opt/ethos/sbin/ethos-overclock > /dev/null 2>&1 &');
				}
				
				echo "Switch complete\r\n";
			}
			
			private function get_machine_config()
			{
				$rig_id = exec('hostname');
				$rig_id = '286c99';
				echo "Getting config data for: $rig_id\r\n";
				$miner_output = file_get_contents('/var/run/miner.output');
				
				$url = $this->api_url.'?rig='.$rig_id;
				$data = array('miner_output' => $miner_output);
				$options = array(
					'http' => array(
						'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
						'method'  => 'POST',
						'content' => http_build_query($data)
					)
				);
				$context  = stream_context_create($options);
				$result = file_get_contents($url, false, $context);
				
				return $result;
			}
		}
		
		$as = new auto_switch();
		$as->run();
?>