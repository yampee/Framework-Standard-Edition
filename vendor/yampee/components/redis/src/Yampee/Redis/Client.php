<?php

/*
 * Yampee Components
 * Open source web development components for PHP 5.
 *
 * @package Yampee Components
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @link http://titouangalopin.com
 */

/**
 * Implements a Redis client for PHP 5.2.
 */
class Yampee_Redis_Client
{
	/**
	 * @var Yampee_Redis_Connection
	 */
	protected $connection;

	/**
	 * @var string
	 */
	protected $host = 'localhost';

	/**
	 * @var integer
	 */
	protected $port = 6379;

	/**
	 * Constructor
	 *
	 * @param string $host
	 * @param int    $port
	 */
	public function __construct($host = 'localhost', $port = 6379)
	{
		$this->host = $host;
		$this->port = $port;
	}

	/**
	 * Connect (or reconnect) to Reddis with given parameters
	 *
	 * @return Yampee_Redis_Client
	 */
	public function connect()
	{
		$this->connection = new Yampee_Redis_Connection($this->host, $this->port);

		return $this;
	}

	/*
	 * Shortcuts
	 */

	/**
	 * Get a value by its key.
	 *
	 * @param $key
	 * @return array|int|null|string
	 * @throws Yampee_Redis_Exception_Error
	 */
	public function get($key)
	{
		if (! $this->has($key)) {
			throw new Yampee_Redis_Exception_Error(sprintf(
				'Key "%s" not found in Redis database.', $key
			));
		}

		return $this->send('get', array($key));
	}

	/**
	 * Check if the given key exists in the database.
	 *
	 * @param $key
	 * @return array|int|null|string
	 */
	public function has($key)
	{
		return $this->send('exists', array($key));
	}

	/**
	 * Set a value and its key.
	 *
	 * @param      $key
	 * @param      $value
	 * @param null $expire
	 * @return array|int|null|string
	 */
	public function set($key, $value, $expire = null)
	{
		if (is_int($expire)) {
			return $this->send('setex', array($key, $expire, $value));
		} else {
			return $this->send('set', array($key, $value));
		}
	}

	/**
	 * Delete a key and its value from the database.
	 *
	 * @param      $key
	 * @return array|int|null|string
	 */
	public function remove($key)
	{
		return $this->send('del', array($key));
	}

	/**
	 * Try to authenticate the user using the given password to the Reddis server.
	 *
	 * @param $password
	 * @return array|int|null|string
	 */
	public function authenticate($password)
	{
		return $this->send('auth', array($password));
	}

	/**
	 * Remove the expiration from a key.
	 *
	 * @param $key
	 * @return array|int|null|string
	 */
	public function persist($key)
	{
		return $this->send('persist', array($key));
	}

	/**
	 * Find all the keys mathcing the pattern.
	 * See more about the pattern on Redis documentation:
	 *      @link http://redis.io/commands/keys
	 *
	 * @param $pattern
	 * @return array|int|null|string
	 */
	public function findKeys($pattern = '*')
	{
		return $this->send('keys', array($pattern));
	}

	/**
	 * Delete all the keys of the currently selected database.
	 *
	 * @return array|int|null|string
	 */
	public function flush()
	{
		return $this->send('flushdb');
	}

	/**
	 * Get information and statistics about the Redis server.
	 *
	 * @return array|int|null|string
	 */
	public function getStats()
	{
		return $this->send('info');
	}

	/**
	 * Get a config element value by its name.
	 *
	 * @param $parameterName
	 * @return array|int|null|string
	 */
	public function getParameter($parameterName)
	{
		return $this->send('config', array('GET', $parameterName));
	}

	/**
	 * Set a config element value by its name.
	 *
	 * @param $parameterName
	 * @param $value
	 * @return array|int|null|string
	 */
	public function setParameter($parameterName, $value)
	{
		return $this->send('config', array('SET', $parameterName, $value));
	}

	/**
	 * Get the Redis database size.
	 *
	 * @return array|int|null|string
	 */
	public function getSize()
	{
		return $this->send('dbsize');
	}

	/*
	 * End shortcuts
	 */

	/**
	 * Send a command to Reddis and return the reply.
	 *
	 * @param       $command
	 * @param array $arguments
	 * @return array|int|null|string
	 */
	public function send($command, array $arguments = array())
	{
		return $this->execute(array_merge(array($command), $arguments));
	}

	/**
	 * Execute a command with Redis and return the result.
	 *
	 * @param array $arguments
	 * @return array|int|null|string
	 * @throws Yampee_Redis_Exception_Command
	 */
	protected function execute(array $arguments)
	{
		// Try to connect
		if (! $this->connection) {
			$this->connect();
		}

		// Create the command
		$command = '*'.count($arguments)."\r\n";

		foreach ($arguments as $argument) {
			$command .= '$'.strlen($argument)."\r\n".$argument."\r\n";
		}

		// Send the command
		if (! $this->connection->send($command)) {
			// If an error occured during first sending, we try to reconnect
			$this->connect();

			if (! $this->connection->send($command)) {
				throw new Yampee_Redis_Exception_Command($command);
			}
		}

		return $this->readReply($command);
	}

	/**
	 * Read a Redis reply.
	 *
	 * @param $command
	 * @return array|int|null|string
	 * @throws Yampee_Redis_Exception_ReadReply
	 * @throws Yampee_Redis_Exception_Error
	 */
	protected function readReply($command)
	{
		$reply = $this->connection->read();

		// If an error occured during first sending, we try to reconnect
		if ($reply === false) {
			$this->connect();

			$reply = $this->connection->read();

			if ($reply === false) {
				throw new Yampee_Redis_Exception_ReadReply($command);
			}
		}

		$reply = trim($reply);

		switch ($reply[0]) {
			// An error occured
			case '-':
				throw new Yampee_Redis_Exception_Error($reply);
				break;

			// Inline response
			case '+':
				return substr($reply, 1);
				break;

			// Bulk response
			case '$':
				$response = null;

				if ($reply == '$-1') {
					return false;
					break;
				}

				$size = intval(substr($reply, 1));

				if ($size > 0) {
					$response = stream_get_contents($this->connection->getSocket(), $size);
				}

				/* discard crlf */
				$this->connection->positionRead(2);

				return $response;
				break;

			// Multi-bulk response
			case '*':
				$count = substr($reply, 1);

				if ($count == '-1') {
					return null;
				}

				$response = array();

				for ($i = 0; $i < $count; $i++) {
					$response[] = $this->readReply($command);
				}

				return $response;
				break;

			// Integer response
			case ':':
				return intval(substr($reply, 1));
				break;

			// Error: not supported
			default:
				throw new Yampee_Redis_Exception_Error('Non-protocol answer: '.print_r($reply, 1));
		}
	}
}