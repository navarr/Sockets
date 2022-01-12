<?php

namespace Navarr\Socket;

use Navarr\Socket\Exception\SocketException;
use Socket as SocketResource;
use Stringable;

use function spl_object_hash;

/**
 * Class Socket.
 *
 * <p>A simple wrapper for PHP's socket functions.</p>
 */
class Socket implements Stringable
{
    /**
     * @var SocketResource Will store a reference to the php socket object.
     */
    protected ?SocketResource $resource = null;

    /**
     * @var int Should be set to one of the php predefined constants for Sockets - AF_UNIX, AF_INET, or AF_INET6
     */
    protected int $domain;

    /**
     * @var int Should be set to one of the php predefined constants for Sockets - SOCK_STREAM, SOCK_DGRAM,
     *          SOCK_SEQPACKET, SOCK_RAW, SOCK_RDM
     */
    protected int $type;

    /**
     * @var int Should be set to the protocol number to be used. Can use getprotobyname to get the value.
     *          Alternatively, there are two predefined constants for Sockets that could be used - SOL_TCP, SOL_UDP
     */
    protected int $protocol;

    /**
     * @var array<string, Socket> An internal storage of php socket resources and their associated Socket object.
     */
    protected static array $map = [];

    /**
     * Sets up the Socket Resource and stores it in the local map.
     *
     * <p>This class uses the <a href="https://en.wikipedia.org/wiki/Factory_(object-oriented_programming)">
     * Factory pattern</a> to create instances. Please use the <code>create</code> method to create new instances
     * of this class.
     *
     * @see Socket::create()
     *
     * @param SocketResource $resource The php socket resource. This is just a reference to the socket object created
     *                                 using the <code>socket_create</code> method.
     */
    protected function __construct(SocketResource $resource)
    {
        $this->resource = $resource;
        self::$map[$this->__toString()] = $this;
    }

    /**
     * Cleans up the Socket and dereferences the internal resource.
     */
    public function __destruct()
    {
        $this->close();
        unset($this->resource);
    }

    /**
     * Return the php socket resource name.
     *
     * <p>Resources are always converted to strings with the structure "Resource id#1", where 1 is the resource number
     * assigned to the resource by PHP at runtime. While the exact structure of this string should not be relied on and
     * is subject to change, it will always be unique for a given resource within the lifetime of the script execution
     * and won't be reused.</p>
     *
     * <p>If the resource object has been dereferrenced (set to <code>null</code>), this will return an empty
     * string.</p>
     *
     * @return string The string representation of the resource or an empty string if the resource was null.
     */
    public function __toString(): string
    {
        return $this->resource ? spl_object_hash($this->resource) : '';
    }

    /**
     * Accept a connection.
     *
     * <p>After the socket socket has been created using <code>create()</code>, bound to a name with
     * <code>bind()</code>, and told to listen for connections with <code>listen()</code>, this function will accept
     * incoming connections on that socket. Once a successful connection is made, a new Socket resource is returned,
     * which may be used for communication. If there are multiple connections queued on the socket, the first will be
     * used. If there are no pending connections, this will block until a connection becomes present. If socket has
     * been made non-blocking using <code>setBlocking()</code>, a <code>SocketException</code> will be thrown.</p>
     *
     * <p>The Socket returned by this method may not be used to accept new connections. The original listening Socket,
     * however, remains open and may be reused.</p>
     *
     * @throws Exception\SocketException If the Socket is set as non-blocking and there are no pending connections.
     *
     * @see Socket::create()
     * @see Socket::bind()
     * @see Socket::listen()
     * @see Socket::setBlocking()
     *
     * @return Socket A new Socket representation of the accepted socket.
     */
    public function accept(): self
    {
        $this->checkInvalidResourceState();

        $return = @socket_accept($this->resource);

        if ($return === false) {
            throw new SocketException($this->resource);
        }

        return new self($return);
    }

    /**
     * Binds a name to a socket.
     *
     * <p>Binds the name given in address to the php socket resource currently in use. This has to be done before a
     * connection is established using <code>connect()</code> or <code>listen()</code>.</p>
     *
     * @param string $address <p>If the socket is of the AF_INET family, the address is an IP in dotted-quad
     *                        notation (e.g. <code>127.0.0.1</code>).</p> <p>If the socket is of the AF_UNIX family, the address is the path
     *                        of the Unix-domain socket (e.g. <code>/tmp/my.sock</code>).</p>
     * @param int $port    <p>(Optional) The port parameter is only used when binding an AF_INET socket, and designates the port
     *                        on which to listen for connections.</p>
     *
     * @return bool <p>Returns <code>true</code> if the bind was successful.</p>
     *@throws Exception\SocketException If the bind was unsuccessful.
     *
     */
    public function bind(string $address, int $port = 0): bool
    {
        return static::exceptionOnFalse(
            $this->resource,
            function ($resource) use ($address, $port) {
                return @socket_bind($resource, $address, $port);
            }
        );
    }

    /**
     * Close the socket.
     *
     * <p>Closes the php socket resource currently in use and removes the reference to it in the internal map.</p>
     */
    public function close(): void
    {
        if ($this->resource === null) {
            return;
        }
        unset(self::$map[$this->__toString()]);
        @socket_close($this->resource);
    }

    /**
     * Connect to a socket.
     *
     * <p>Initiate a connection to the address given using the current php socket resource, which must be a valid
     * socket resource created with <code>create()</code>.
     *
     * @param string $address <p>The address parameter is either an IPv4 address in dotted-quad notation (e.g.
     *                        <code>127.0.0.1</code>) if the socket is AF_INET, a valid IPv6 address (e.g. <code>::1</code>) if IPv6 support
     *                        is enabled and the socket is AF_INET6, or the pathname of a Unix domain socket, if the socket family is AF_UNIX.
     *                        </p>
     * @param int    $port    <p>(Optional) The port parameter is only used and is mandatory when connecting to an AF_INET or
     *                        an AF_INET6 socket, and designates the port on the remote host to which a connection should be made.</p>
     *
     * @throws Exception\SocketException If the connect was unsuccessful or if the socket is non-blocking.
     *
     * @see Socket::bind()
     * @see Socket::listen()
     * @see Socket::create()
     *
     * @return bool <p>Returns <code>true</code> if the connect was successful.
     */
    public function connect(string $address, int $port = 0): bool
    {
        return static::exceptionOnFalse(
            $this->resource,
            static function ($resource) use ($address, $port) {
                return @socket_connect($resource, $address, $port);
            }
        );
    }

    /**
     * Build Socket objects based on an array of php socket resources.
     *
     * @param SocketResource[] $resources A list of php socket resource objects.
     *
     * @return Socket[] <p>Returns an array of Socket objects built from the given php socket resources.</p>
     */
    protected static function constructFromResources(array $resources): array
    {
        return array_map(static function ($resource) {
            return new self($resource);
        }, $resources);
    }

    /**
     * Create a socket.
     *
     * <p>Creates and returns a Socket. A typical network connection is made up of two sockets, one performing the role
     * of the client, and another performing the role of the server.</p>
     *
     * @param int $domain   <p>The domain parameter specifies the protocol family to be used by the socket.</p><p>
     *                      <code>AF_INET</code> - IPv4 Internet based protocols. TCP and UDP are common protocols of this protocol family.
     *                      </p><p><code>AF_INET6</code> - IPv6 Internet based protocols. TCP and UDP are common protocols of this protocol
     *                      family.</p><p><code>AF_UNIX</code> - Local communication protocol family. High efficiency and low overhead make
     *                      it a great form of IPC (Interprocess Communication).</p>
     * @param int $type     <p>The type parameter selects the type of communication to be used by the socket.</p><p>
     *                      <code>SOCK_STREAM</code> - Provides sequenced, reliable, full-duplex, connection-based byte streams. An
     *                      out-of-band data transmission mechanism may be supported. The TCP protocol is based on this socket type.</p><p>
     *                      <code>SOCK_DGRAM</code> - Supports datagrams (connectionless, unreliable messages of a fixed maximum length).
     *                      The UDP protocol is based on this socket type.</p><p><code>SOCK_SEQPACKET</code> - Provides a sequenced,
     *                      reliable, two-way connection-based data transmission path for datagrams of fixed maximum length; a consumer is
     *                      required to read an entire packet with each read call.</p><p><code>SOCK_RAW</code> - Provides raw network
     *                      protocol access. This special type of socket can be used to manually construct any type of protocol. A common
     *                      use for this socket type is to perform ICMP requests (like ping).</p><p><code>SOCK_RDM</code> - Provides a
     *                      reliable datagram layer that does not guarantee ordering. This is most likely not implemented on your operating
     *                      system.</p>
     * @param int $protocol <p>The protocol parameter sets the specific protocol within the specified domain to be used
     *                      when communicating on the returned socket. The proper value can be retrieved by name by using
     *                      <code>getprotobyname()</code>. If the desired protocol is TCP, or UDP the corresponding constants
     *                      <code>SOL_TCP</code>, and <code>SOL_UDP</code> can also be used.<p><p>Some of the common protocol types</p><p>
     *                      icmp - The Internet Control Message Protocol is used primarily by gateways and hosts to report errors in
     *                      datagram communication. The "ping" command (present in most modern operating systems) is an example application
     *                      of ICMP.</p><p>udp - The User Datagram Protocol is a connectionless, unreliable, protocol with fixed record
     *                      lengths. Due to these aspects, UDP requires a minimum amount of protocol overhead.</p><p>tcp - The Transmission
     *                      Control Protocol is a reliable, connection based, stream oriented, full duplex protocol. TCP guarantees that all
     *                      data packets will be received in the order in which they were sent. If any packet is somehow lost during
     *                      communication, TCP will automatically retransmit the packet until the destination host acknowledges that packet.
     *                      For reliability and performance reasons, the TCP implementation itself decides the appropriate octet boundaries
     *                      of the underlying datagram communication layer. Therefore, TCP applications must allow for the possibility of
     *                      partial record transmission.</p>
     *
     * @throws Exception\SocketException If there is an error creating the php socket.
     *
     * @return Socket Returns a Socket object based on the successful creation of the php socket.
     */
    public static function create(int $domain, int $type, int $protocol): self
    {
        $return = @socket_create($domain, $type, $protocol);

        if ($return === false) {
            throw new SocketException();
        }

        $socket = new self($return);
        $socket->domain = $domain;
        $socket->type = $type;
        $socket->protocol = $protocol;

        return $socket;
    }

    /**
     * Opens a socket on port to accept connections.
     *
     * <p>Creates a new socket resource of type <code>AF_INET</code> listening on all local interfaces on the given
     * port waiting for new connections.</p>
     *
     * @param int $port    The port on which to listen on all interfaces.
     * @param int $backlog <p>The backlog parameter defines the maximum length the queue of pending connections may
     *                     grow to. <code>SOMAXCONN</code> may be passed as the backlog parameter.</p>
     *
     * @throws Exception\SocketException If the socket is not successfully created.
     *
     * @see Socket::create()
     * @see Socket::bind()
     * @see Socket::listen()
     *
     * @return Socket Returns a Socket object based on the successful creation of the php socket.
     */
    public static function createListen(int $port, int $backlog = 128): self
    {
        $return = @socket_create_listen($port, $backlog);

        if ($return === false) {
            throw new SocketException();
        }

        $socket = new self($return);
        $socket->domain = AF_INET;

        return $socket;
    }

    /**
     * Creates a pair of indistinguishable sockets and stores them in an array.
     *
     * <p>Creates two connected and indistinguishable sockets. This function is commonly used in IPC (InterProcess
     * Communication).</p>
     *
     * @param int $domain   <p>The domain parameter specifies the protocol family to be used by the socket. See
     *                      <code>create()</code> for the full list.</p>
     * @param int $type     <p>The type parameter selects the type of communication to be used by the socket. See
     *                      <code>create()</code> for the full list.</p>
     * @param int $protocol <p>The protocol parameter sets the specific protocol within the specified domain to be used
     *                      when communicating on the returned socket. The proper value can be retrieved by name by using
     *                      <code>getprotobyname()</code>. If the desired protocol is TCP, or UDP the corresponding constants
     *                      <code>SOL_TCP</code>, and <code>SOL_UDP</code> can also be used. See <code>create()</code> for the full list of
     *                      supported protocols.
     *
     * @throws Exception\SocketException If the creation of the php sockets is not successful.
     *
     * @see Socket::create()
     *
     * @return Socket[] An array of Socket objects containing identical sockets.
     */
    public static function createPair(int $domain, int $type, int $protocol): array
    {
        $array = [];
        $return = @socket_create_pair($domain, $type, $protocol, $array);

        if ($return === false) {
            throw new SocketException();
        }

        $sockets = self::constructFromResources($array);

        foreach ($sockets as $socket) {
            $socket->domain = $domain;
            $socket->type = $type;
            $socket->protocol = $protocol;
        }

        return $sockets;
    }

    /**
     * Gets socket options.
     *
     * <p>Retrieves the value for the option specified by the optname parameter for the current socket.</p>
     *
     * @param int $level   <p>The level parameter specifies the protocol level at which the option resides. For example,
     *                     to retrieve options at the socket level, a level parameter of <code>SOL_SOCKET</code> would be used. Other
     *                     levels, such as <code>TCP</code>, can be used by specifying the protocol number of that level. Protocol numbers
     *                     can be found by using the <code>getprotobyname()</code> function.
     * @param int $optname <p><b>Available Socket Options</b></p><p><code>SO_DEBUG</code> - Reports whether debugging
     *                     information is being recorded. Returns int.</p><p><code>SO_BROADCAST</code> - Reports whether transmission of
     *                     broadcast messages is supported. Returns int.</p><p><code>SO_REUSERADDR</code> - Reports whether local addresses
     *                     can be reused. Returns int.</p><p><code>SO_KEEPALIVE</code> - Reports whether connections are kept active with
     *                     periodic transmission of messages. If the connected socket fails to respond to these messages, the connection is
     *                     broken and processes writing to that socket are notified with a SIGPIPE signal. Returns int.</p><p>
     *                     <code>SO_LINGER</code> - Reports whether the socket lingers on <code>close()</code> if data is present. By
     *                     default, when the socket is closed, it attempts to send all unsent data. In the case of a connection-oriented
     *                     socket, <code>close()</code> will wait for its peer to acknowledge the data. If <code>l_onoff</code> is non-zero
     *                     and <code>l_linger</code> is zero, all the unsent data will be discarded and RST (reset) is sent to the peer in
     *                     the case of a connection-oriented socket. On the other hand, if <code>l_onoff</code> is non-zero and
     *                     <code>l_linger</code> is non-zero, <code>close()</code> will block until all the data is sent or the time
     *                     specified in <code>l_linger</code> elapses. If the socket is non-blocking, <code>close()</code> will fail and
     *                     return an error. Returns an array with two keps: <code>l_onoff</code> and <code>l_linger</code>.</p><p>
     *                     <code>SO_OOBINLINE</code> - Reports whether the socket leaves out-of-band data inline. Returns int.</p><p>
     *                     <code>SO_SNDBUF</code> - Reports the size of the send buffer. Returns int.</p><p><code>SO_RCVBUF</code> -
     *                     Reports the size of the receive buffer. Returns int.</p><p><code>SO_ERROR</code> - Reports information about
     *                     error status and clears it. Returns int.</p><p><code>SO_TYPE</code> - Reports the socket type (e.g.
     *                     <code>SOCK_STREAM</code>). Returns int.</p><p><code>SO_DONTROUTE</code> - Reports whether outgoing messages
     *                     bypass the standard routing facilities. Returns int.</p><p><code>SO_RCVLOWAT</code> - Reports the minimum number
     *                     of bytes to process for socket input operations. Returns int.</p><p><code>SO_RCVTIMEO</code> - Reports the
     *                     timeout value for input operations. Returns an array with two keys: <code>sec</code> which is the seconds part
     *                     on the timeout value and <code>usec</code> which is the microsecond part of the timeout value.</p><p>
     *                     <code>SO_SNDTIMEO</code> - Reports the timeout value specifying the amount of time that an output function
     *                     blocks because flow control prevents data from being sent. Returns an array with two keys: <code>sec</code>
     *                     which is the seconds part on the timeout value and <code>usec</code> which is the microsecond part of the
     *                     timeout value.</p><p><code>SO_SNDLOWAT</code> - Reports the minimum number of bytes to process for socket output
     *                     operations. Returns int.</p><p><code>TCP_NODELAY</code> - Reports whether the Nagle TCP algorithm is disabled.
     *                     Returns int.</p><p><code>IP_MULTICAST_IF</code> - The outgoing interface for IPv4 multicast packets. Returns the
     *                     index of the interface (int).</p><p><code>IPV6_MULTICAST_IF</code> - The outgoing interface for IPv6 multicast
     *                     packets. Returns the same thing as <code>IP_MULTICAST_IF</code>.</p><p><code>IP_MULTICAST_LOOP</code> - The
     *                     multicast loopback policy for IPv4 packets, which determines whether multicast packets sent by this socket also
     *                     reach receivers in the same host that have joined the same multicast group on the outgoing interface used by
     *                     this socket. This is the case by default. Returns int.</p><p><code>IPV6_MULTICAST_LOOP</code> - Analogous to
     *                     <code>IP_MULTICAST_LOOP</code>, but for IPv6. Returns int.</p><p><code>IP_MULTICAST_TTL</code> - The
     *                     time-to-live of outgoing IPv4 multicast packets. This should be a value between 0 (don't leave the interface)
     *                     and 255. The default value is 1 (only the local network is reached). Returns int.</p><p>
     *                     <code>IPV6_MULTICAST_HOPS</code> - Analogous to <code>IP_MULTICAST_TTL</code>, but for IPv6 packets. The value
     *                     -1 is also accepted, meaning the route default should be used. Returns int.</p>
     *
     * @throws Exception\SocketException If there was an error retrieving the option.
     *
     * @return mixed See the descriptions based on the option being requested above.
     */
    public function getOption(int $level, int $optname): mixed
    {
        return static::exceptionOnFalse(
            $this->resource,
            static function ($resource) use ($level, $optname) {
                return @socket_get_option($resource, $level, $optname);
            }
        );
    }

    /**
     * Queries the remote side of the given socket which may either result in host/port or in a Unix filesystem
     * path, dependent on its type.
     *
     * @param string $address <p>If the given socket is of type <code>AF_INET</code> or <code>AF_INET6</code>,
     *                        <code>getPeerName()</code> will return the peers (remote) IP address in appropriate notation (e.g.
     *                        <code>127.0.0.1</code> or <code>fe80::1</code>) in the address parameter and, if the optional port parameter is
     *                        present, also the associated port.</p><p>If the given socket is of type <code>AF_UNIX</code>,
     *                        <code>getPeerName()</code> will return the Unix filesystem path (e.g. <code>/var/run/daemon.sock</cod>) in the
     *                        address parameter.</p>
     * @param int    $port    (Optional) If given, this will hold the port associated to the address.
     *
     * @throws Exception\SocketException <p>If the retrieval of the peer name fails or if the socket type is not
     *                                   <code>AF_INET</code>, <code>AF_INET6</code>, or <code>AF_UNIX</code>.</p>
     *
     * @return bool <p>Returns <code>true</code> if the retrieval of the peer name was successful.</p>
     */
    public function getPeerName(string &$address, int &$port): bool
    {
        return static::exceptionOnFalse(
            $this->resource,
            static function ($resource) use (&$address, &$port) {
                return @socket_getpeername($resource, $address, $port);
            }
        );
    }

    /**
     * Queries the local side of the given socket which may either result in host/port or in a Unix filesystem path,
     * dependent on its type.
     *
     * <p><b>Note:</b> <code>getSockName()</code> should not be used with <code>AF_UNIX</code> sockets created with
     * <code>connect()</code>. Only sockets created with <code>accept()</code> or a primary server socket following a
     * call to <code>bind()</code> will return meaningful values.</p>
     *
     * @param string $address <p>If the given socket is of type <code>AF_INET</code> or <code>AF_INET6</code>,
     *                        <code>getSockName()</code> will return the local IP address in appropriate notation (e.g.
     *                        <code>127.0.0.1</code> or <code>fe80::1</code>) in the address parameter and, if the optional port parameter is
     *                        present, also the associated port.</p><p>If the given socket is of type <code>AF_UNIX</code>,
     *                        <code>getSockName()</code> will return the Unix filesystem path (e.g. <code>/var/run/daemon.sock</cod>) in the
     *                        address parameter.</p>
     * @param int    $port    If provided, this will hold the associated port.
     *
     * @throws Exception\SocketException <p>If the retrieval of the socket name fails or if the socket type is not
     *                                   <code>AF_INET</code>, <code>AF_INET6</code>, or <code>AF_UNIX</code>.</p>
     *
     * @return bool <p>Returns <code>true</code> if the retrieval of the socket name was successful.</p>
     */
    public function getSockName(string &$address, int &$port): bool
    {
        if (!in_array($this->domain, [AF_UNIX, AF_INET, AF_INET6])) {
            return false;
        }

        return static::exceptionOnFalse(
            $this->resource,
            static function ($resource) use (&$address, &$port) {
                return @socket_getsockname($resource, $address, $port);
            }
        );
    }

    /**
     * Imports a stream.
     *
     * <p>Imports a stream that encapsulates a socket into a socket extension resource.</p>
     *
     * @param resource $stream The stream resource to import.
     *
     * @throws Exception\SocketException If the import of the stream is not successful.
     *
     * @return Socket Returns a Socket object based on the stream.
     */
    public static function importStream($stream): self
    {
        $return = @socket_import_stream($stream);

        if ($return === false || is_null($return)) {
            throw new SocketException($stream);
        }

        return new self($return);
    }

    /**
     * Listens for a connection on a socket.
     *
     * <p>After the socket has been created using <code>create()</code> and bound to a name with <code>bind()</code>,
     * it may be told to listen for incoming connections on socket.</p>
     *
     * @param int $backlog <p>A maximum of backlog incoming connections will be queued for processing. If a connection
     *                     request arrives with the queue full the client may receive an error with an indication of ECONNREFUSED, or, if
     *                     the underlying protocol supports retransmission, the request may be ignored so that retries may succeed.</p><p>
     *                     <b>Note:</b> The maximum number passed to the backlog parameter highly depends on the underlying platform. On
     *                     Linux, it is silently truncated to <code>SOMAXCONN</code>. On win32, if passed <code>SOMAXCONN</code>, the
     *                     underlying service provider responsible for the socket will set the backlog to a maximum reasonable value. There
     *                     is no standard provision to find out the actual backlog value on this platform.</p>
     *
     * @throws Exception\SocketException If the listen fails.
     *
     * @return bool <p>Returns <code>true</code> on success.
     */
    public function listen(int $backlog = 0): bool
    {
        return static::exceptionOnFalse(
            $this->resource,
            static function ($resource) use ($backlog) {
                return @socket_listen($resource, $backlog);
            }
        );
    }

    /**
     * reads a maximum of length bytes from a socket.
     *
     * <p>Reads from the socket created by the <code>create()</code> or <code>accept()</code> functions.</p>
     *
     * @param int $length <p>The maximum number of bytes read is specified by the length parameter. Otherwise you can
     *                    use <code>\r</code>, <code>\n</code>, or <code>\0</code> to end reading (depending on the type parameter, see
     *                    below).</p>
     * @param int $type   <p>(Optional) type parameter is a named constant:<ul><li><code>PHP_BINARY_READ</code> (Default)
     *                    - use the system <code>recv()</code> function. Safe for reading binary data.</li><li>
     *                    <code>PHP_NORMAL_READ</code> - reading stops at <code>\n</code> or <code>\r</code>.</li></ul></p>
     *
     * @throws Exception\SocketException If there was an error reading or if the host closed the connection.
     *
     * @see Socket::create()
     * @see Socket::accept()
     *
     * @return string Returns the data as a string. Returns a zero length string ("") when there is no more data to
     *                read.
     */
    public function read(int $length, int $type = PHP_BINARY_READ): string
    {
        return static::exceptionOnFalse(
            $this->resource,
            static function ($resource) use ($length, $type) {
                return @socket_read($resource, $length, $type);
            }
        );
    }

    /**
     * Receives data from a connected socket.
     *
     * <p>Receives length bytes of data in buffer from the socket. <code>receive()</code> can be used to gather data
     * from connected sockets. Additionally, one or more flags can be specified to modify the behaviour of the
     * function.</p><p>buffer is passed by reference, so it must be specified as a variable in the argument list. Data
     * read from socket by <code>receive()</code> will be returned in buffer.</p>
     *
     * @param string $buffer <p>The data received will be fetched to the variable specified with buffer. If an error
     *                       occurs, if the connection is reset, or if no data is available, buffer will be set to <code>NULL</code>.</p>
     * @param int    $length Up to length bytes will be fetched from remote host.
     * @param int    $flags  <p>The value of flags can be any combination of the following flags, joined with the binary OR
     *                       (<code>|</code>) operator.<ul><li><code>MSG_OOB</code> - Process out-of-band data.</li><li><code>MSG_PEEK</code>
     *                       - Receive data from the beginning of the receive queue without removing it from the queue.</li><li>
     *                       <code>MSG_WAITALL</code> - Block until at least length are received. However, if a signal is caught or the
     *                       remote host disconnects, the function may return less data.</li><li><code>MSG_DONTWAIT</code> - With this flag
     *                       set, the function returns even if it would normally have blocked.</li></ul></p>
     *
     * @throws Exception\SocketException If there was an error receiving data.
     *
     * @return int Returns the number of bytes received.
     */
    public function receive(string &$buffer, int $length, int $flags): int
    {
        return static::exceptionOnFalse(
            $this->resource,
            static function ($resource) use (&$buffer, $length, $flags) {
                return @socket_recv($resource, $buffer, $length, $flags);
            }
        );
    }

    /**
     * Runs the select() system call on the given arrays of sockets with a specified timeout.
     *
     * <p>accepts arrays of sockets and waits for them to change status. Those coming with BSD sockets background will
     * recognize that those socket resource arrays are in fact the so-called file descriptor sets. Three independent
     * arrays of socket resources are watched.</p><p><b>WARNING:</b> On exit, the arrays are modified to indicate which
     * socket resource actually changed status.</p><p>ou do not need to pass every array to <code>select()</code>. You
     * can leave it out and use an empty array or <code>NULL</code> instead. Also do not forget that those arrays are
     * passed by reference and will be modified after <code>select()</code> returns.
     *
     * @param Socket[] &$read               <p>The sockets listed in the read array will be watched to see if characters become
     *                                      available for reading (more precisely, to see if a read will not block - in particular, a socket resource is also
     *                                      ready on end-of-file, in which case a <code>read()</code> will return a zero length string).</p>
     * @param Socket[] &$write              The sockets listed in the write array will be watched to see if a write will not block.
     * @param Socket[] &$except             he sockets listed in the except array will be watched for exceptions.
     * @param ?int     $timeoutSeconds      The seconds portion of the timeout parameters (in conjunction with
     *                                      timeoutMilliseconds). The timeout is an upper bound on the amount of time elapsed before <code>select()</code>
     *                                      returns. timeoutSeconds may be zero, causing the <code>select()</code> to return immediately. This is useful for
     *                                      polling. If timeoutSeconds is <code>NULL</code> (no timeout), the <code>select()</code> can block
     *                                      indefinitely.</p>
     * @param int      $timeoutMilliseconds See the description for timeoutSeconds.
     *
     * @throws SocketException If there was an error.
     *
     * @return int Returns the number of socket resources contained in the modified arrays, which may be zero if the
     *             timeout expires before anything interesting happens.
     */
    public static function select(
        array &$read,
        array &$write,
        array &$except,
        ?int $timeoutSeconds,
        int $timeoutMilliseconds = 0
    ): int {
        $readSockets = self::mapClassToRawSocket($read);
        $writeSockets = self::mapClassToRawSocket($write);
        $exceptSockets = self::mapClassToRawSocket($except);

        $return = @socket_select(
            $readSockets,
            $writeSockets,
            $exceptSockets,
            $timeoutSeconds,
            $timeoutMilliseconds
        );

        if ($return === false) {
            throw new SocketException();
        }

        $read = [];
        $write = [];
        $except = [];

        if ($readSockets) {
            $read = static::mapRawSocketToClass($readSockets);
        }
        if ($writeSockets) {
            $write = static::mapRawSocketToClass($writeSockets);
        }
        if ($exceptSockets) {
            $except = static::mapRawSocketToClass($exceptSockets);
        }

        return $return;
    }

    /**
     * Maps an array of Sockets to an array of socket resources.
     *
     * @param Socket[] $sockets An array of sockets to map.
     *
     * @return SocketResource[] Returns the corresponding array of resources.
     */
    protected static function mapClassToRawSocket(array $sockets): array
    {
        return array_filter(
            array_map(
                static function (Socket $socket) {
                    return $socket->resource;
                },
                $sockets
            )
        );
    }

    /**
     * Maps an array of socket resources to an array of Sockets.
     *
     * @param SocketResource[] $sockets An array of socket resources to map.
     *
     * @return Socket[] Returns the corresponding array of Socket objects.
     */
    protected static function mapRawSocketToClass(array $sockets): array
    {
        return array_map(
            static function ($rawSocket) {
                return self::$map[spl_object_hash($rawSocket)];
            },
            $sockets
        );
    }

    /**
     * Performs the closure function.  If it returns false, throws a SocketException using the provided resource.
     *
     * @template T
     * @param ?SocketResource $resource Socket Resource
     * @param callable(SocketResource):T $closure  A function that takes 1 parameter (a socket resource)
     * @return T
     *
     * @throws SocketException
     */
    protected static function exceptionOnFalse(?SocketResource $resource, callable $closure): mixed
    {
        if ($resource === null) {
            throw new SocketException('Socket is not connected');
        }

        $result = $closure($resource);

        if ($result === false) {
            throw new SocketException($resource);
        }

        return $result;
    }

    /**
     * Write to a socket.
     *
     * <p>The function <code>write()</code> writes to the socket from the given buffer.</p>
     *
     * @param string $buffer The buffer to be written.
     * @param ?int   $length The optional parameter length can specify an alternate length of bytes written to the
     * socket. If this length is greater than the buffer length, it is silently truncated to the length of the buffer.
     *
     * @throws Exception\SocketException If there was a failure.
     *
     * @return int Returns the number of bytes successfully written to the socket.
     */
    public function write(string $buffer, int $length = null): int
    {
        if (null === $length) {
            $length = strlen($buffer);
        }

        // make sure everything is written
        do {
            $return = @socket_write($this->resource, $buffer, $length);

            if (false !== $return && $return < $length) {
                $buffer = substr($buffer, $return);
                $length -= $return;
            } else {
                break;
            }
        } while (true);

        if ($return === false) {
            throw new SocketException($this->resource);
        }

        return $return;
    }

    /**
     * Sends data to a connected socket.
     *
     * <p>Sends length bytes to the socket from buffer.</p>
     *
     * @param string $buffer A buffer containing the data that will be sent to the remote host.
     * @param int    $flags  <p>The value of flags can be any combination of the following flags, joined with the binary OR
     *                       (<code>|</code>) operator.<ul><li><code>MSG_OOB</code> - Send OOB (out-of-band) data.</li><li>
     *                       <code>MSG_EOR</code> - Indicate a record mark. The sent data completes the record.</li><li><code>MSG_EOF</code> -
     *                       Close the sender side of the socket and include an appropriate notification of this at the end of the sent data.
     *                       The sent data completes the transaction.</li><li><code>MSG_DONTROUTE</code> - Bypass routing, use direct
     *                       interface.</li></ul></p>
     * @param int    $length The number of bytes that will be sent to the remote host from buffer.
     *
     * @throws Exception\SocketException If there was a failure.
     *
     * @return int Returns the number of bytes sent.
     */
    public function send(string $buffer, int $flags = 0, int $length = null): int
    {
        $this->checkInvalidResourceState();

        if (null === $length) {
            $length = strlen($buffer);
        }

        // make sure everything is written
        do {
            $return = @socket_send($this->resource, $buffer, $length, $flags);

            if (false !== $return && $return < $length) {
                $buffer = substr($buffer, $return);
                $length -= $return;
            } else {
                break;
            }
        } while (true);

        if ($return === false) {
            throw new SocketException($this->resource);
        }

        return $return;
    }

    /**
     * Set the socket to blocking / non blocking.
     *
     * <p>Removes (blocking) or set (non blocking) the <code>O_NONBLOCK</code> flag on the socket.</p><p>When an
     * operation is performed on a blocking socket, the script will pause its execution until it receives a signal or it
     * can perform the operation.</p><p>When an operation is performed on a non-blocking socket, the script will not
     * pause its execution until it receives a signal or it can perform the operation. Rather, if the operation would
     * result in a block, the called function will fail.</p>
     *
     * @param bool $bool Flag to indicate if the Socket should block (<code>true</code>) or not block
     *                   (<code>false</code>).
     * @throws SocketException
     */
    public function setBlocking(bool $bool): void
    {
        $this->checkInvalidResourceState();
        if ($bool) {
            @socket_set_block($this->resource);
        } else {
            @socket_set_nonblock($this->resource);
        }
    }

    /**
     * @throws SocketException
     */
    private function checkInvalidResourceState(): void
    {
        if (null === $this->resource) {
            throw new SocketException('Socket is not connected');
        }
    }
}
