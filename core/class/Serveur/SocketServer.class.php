<?php
require_once "SocketException.class.php";
require_once "SocketClient.class.php";

class SocketServer extends KNXnet {
	
	protected $sockServer;
	protected $address;
	protected $port;
	protected $_listenLoop;
	
	public function __construct( $port = 6720, $address = '127.0.0.1' ) {
		$this->address = $address;
		$this->port = $port;
		$this->_listenLoop = false;
	}
	
	public function init() {
		$this->_createSocket();
		$this->_bindSocket();
		if (!$this->Open_GroupSocket())
			throw new Exception(__($this->getLastError(), __FILE__));
	}
	
	private function _createSocket() {
		$this->sockServer = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if( $this->sockServer === false ) {
			throw new SocketException( 
				SocketException::CANT_CREATE_SOCKET, 
				socket_strdebug(socket_last_debug()) );
		}
		
		socket_set_option($this->sockServer, SOL_SOCKET, SO_REUSEADDR, 1);
	}
	
	private function _bindSocket() {
		if( socket_bind($this->sockServer, $this->address, $this->port) === false ) {
			throw new SocketException( 
				SocketException::CANT_BIND_SOCKET, 
				socket_strdebug(socket_last_debug( $this->sockServer ) ) );
		}
	}
	
	public function listen() {
		if( socket_listen($this->sockServer, 5) === false) {
			throw new SocketException( 
				SocketException::CANT_BIND_SOCKET, 
				socket_strdebug(socket_last_debug( $this->sockServer ) ) );
		}

		$this->_listenLoop = true;
		$this->beforeServerLoop();
		$this->serverLoop();
		$this->CloseConnexion();
		socket_close( $this->sockServer );
		log::add('KnxServer', 'debug',"Server disconnected on ".$this->address.":". $this->port."...");
	}
	
	protected function beforeServerLoop() {
		log::add('KnxServer', 'debug',"Listening on ".$this->address.":". $this->port."...");
	}	
	protected function serverLoop() {
		while( $this->_listenLoop ) {
			if( ( $client = @socket_accept( $this->sockServer ) ) === false ) {
				throw new SocketException(
						SocketException::CANT_ACCEPT,
						socket_strdebug(socket_last_debug( $this->sockServer ) ) );
				continue;
			}
				
			$socketClient = new SocketClient( $client );
			$this->onConnect($socketClient);
		}
	}
	private function onConnect($client) {
		$pid = pcntl_fork();
		
		if ($pid == -1) {
			 die('could not fork');
		} else if ($pid) {
			// parent process
			return;
		}
		$client->setReadSurveil(true);
		$read = '';
		log::add('KnxServer','debug', $client->getAddress() ." Connected at port ".$client->getPort());
		while($client->getReadSurveil()) {
			$read=$client->read();
			if($read!=null&&$read!='')
			{
				log::add('KnxServer','debug', 'Exécution de l\'ordre :' . $read);
				switch ($read)
				{
					case 'BusMonitorInfo':
						if (!$this->getGroup_Src())
							log::add('KnxServer', 'debug', $this->getLastError());
						$KnxFrameInfo=array(
							"Mode"=>$this->getACPI(),
							"Data"=> $this->getData(),
							"AdrSource"=>$this->getAdrSource(),
							"AdrGroup"=>$this->getAdrGroup()
							);
						$client->send(json_encode($KnxFrameInfo));
					break;
					case 'SendData':
						$client->send('true');
						$dest=$client->read();
						log::add('KnxServer','debug', 'Addresses de destination :' . $dest);
						$client->send('true');
						$data=$client->read();
						log::add('KnxServer','debug', 'data :' . $data);
						if (!$Valeur=$this->SendAPDU($dest,json_decode($data,true)))
							log::add('KnxServer','debug',$this->getLastError());
						$client->send(json_encode($Valeur));
					break;
					case 'ReadData':
						$client->send('true');
						$dest=$client->read();
						if (!$Valeur=$this->ReadAPDU($dest))
							log::add('KnxServer','debug',$this->getLastError());	
						$client->send(json_encode($Valeur));
					break;
					case 'close':
						$client->setReadSurveil(false);
						$client->close();
						log::add('KnxServer','debug', $client->getAddress() ." Disconnected");
					break;
					case 'ServerClose':
						$client->setReadSurveil(false);
						$this->_listenLoop = false;
						$client->close();
						log::add('KnxServer','debug', $client->getAddress() ." Disconnected");
					break;
					default:
						$this->Send($read);
						$client->send('true');
					break;
				}
			}
		}
	}
}

