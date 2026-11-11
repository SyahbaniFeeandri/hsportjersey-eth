// SPDX-License-Identifier: MIT
pragma solidity ^0.8.19;

/// @title HSportStore
/// @notice Escrow dan audit trail on-chain untuk order H-Sport Jersey.
/// @dev V2 (Minggu 4): enum 8 state, externalOrderHash, operator, alur kripto
///      lengkap termasuk cancel/refund. Interface V1 tetap dipertahankan.
///      Spesifikasi: docs/week2/02_CONTRACT_SPEC_FINAL.md
contract HSportStore {
    /// @notice Status order sesuai state machine 8 state. Urutan tidak boleh berubah.
    enum OrderStatus {
        Created,          // 0
        AwaitingPayment,  // 1
        Paid,             // 2
        InProduction,     // 3
        ReadyToShip,      // 4
        Shipped,          // 5
        Completed,        // 6
        Cancelled         // 7
    }

    /// @notice Data order on-chain. Detail bisnis tetap di MySQL.
    struct Order {
        uint256 id;
        bytes32 externalOrderHash;
        address buyer;
        uint256 totalAmount;
        OrderStatus status;
        bool paymentRecorded;
        uint256 createdAt;
        uint256 updatedAt;
        string orderNumber;
    }

    /// @notice Dana yang dikunci sampai order selesai atau dibatalkan.
    struct PaymentEscrow {
        uint256 orderId;
        address buyer;
        uint256 amount;
        bool released;
        bool refunded;
    }

    /// @notice Pemilik toko dan satu-satunya pengelola operator.
    address public owner;
    /// @notice Jumlah order; ID order dimulai dari 1.
    uint256 public orderCount;

    mapping(uint256 => Order) public orders;
    mapping(uint256 => PaymentEscrow) public escrows;
    mapping(bytes32 => uint256) public orderIdByHash;
    mapping(address => bool) public operators;

    /// @notice Order baru dicatat on-chain.
    event OrderCreated(uint256 indexed orderId, address indexed buyer, bytes32 indexed externalOrderHash, uint256 amount, string orderNumber);
    /// @notice Operator mencatat pembayaran.
    event PaymentRecorded(uint256 indexed orderId, address indexed operator, uint256 amount);
    /// @notice Setiap perubahan status order.
    event OrderStatusUpdated(uint256 indexed orderId, OrderStatus oldStatus, OrderStatus newStatus, address indexed operator);
    /// @notice Order diserahkan ke kurir.
    event OrderShipped(uint256 indexed orderId, address indexed operator);
    /// @notice Pembeli mengonfirmasi penerimaan.
    event OrderCompleted(uint256 indexed orderId, address indexed buyer);
    /// @notice Escrow dirilis ke pemilik toko.
    event EscrowReleased(uint256 indexed orderId, address indexed seller, uint256 amount);
    /// @notice Order dibatalkan (dipakai di V2).
    event OrderCancelled(uint256 indexed orderId, address indexed actor, string reason);
    /// @notice Escrow dikembalikan ke pembeli (dipakai di V2).
    event EscrowRefunded(uint256 indexed orderId, address indexed buyer, uint256 amount);
    /// @notice Operator ditambahkan atau dinonaktifkan.
    event OperatorUpdated(address indexed operator, bool active);

    /// @dev Hanya pemilik toko.
    modifier onlyOwner() {
        require(msg.sender == owner, "Not owner");
        _;
    }

    /// @dev Pemilik toko selalu dianggap operator.
    modifier onlyOperator() {
        require(msg.sender == owner || operators[msg.sender], "Not operator");
        _;
    }

    /// @dev Hanya pembeli order tersebut.
    modifier onlyBuyer(uint256 _orderId) {
        require(orders[_orderId].buyer == msg.sender, "Not buyer");
        _;
    }

    /// @dev Order harus ada; ID 0 dan ID di atas orderCount dianggap tidak ada.
    modifier orderExists(uint256 _orderId) {
        require(_orderId > 0 && _orderId <= orderCount, "Order not found");
        _;
    }

    /// @dev Order Completed/Cancelled tidak boleh diubah lagi.
    modifier notFinal(uint256 _orderId) {
        OrderStatus current = orders[_orderId].status;
        require(current != OrderStatus.Completed && current != OrderStatus.Cancelled, "Order is final");
        _;
    }

    constructor() {
        owner = msg.sender;
    }

    /// @notice Membuat order baru dan mengunci dana pembeli di escrow.
    /// @param _externalOrderHash Hash kanonik order off-chain: keccak256(abi.encode(orderNumber, buyer, amount)).
    /// @param _orderNumber Nomor order tampilan, maksimum 64 byte.
    /// @param _amount Nilai escrow dalam wei; harus sama dengan msg.value.
    /// @return ID order yang baru dibuat.
    function createOrder(
        bytes32 _externalOrderHash,
        string calldata _orderNumber,
        uint256 _amount
    ) external payable returns (uint256) {
        require(_amount > 0, "Amount must be > 0");
        require(msg.value == _amount, "Incorrect amount");
        require(bytes(_orderNumber).length > 0 && bytes(_orderNumber).length <= 64, "Order number required");
        require(orderIdByHash[_externalOrderHash] == 0, "Duplicate order hash");

        orderCount++;

        orders[orderCount] = Order({
            id: orderCount,
            externalOrderHash: _externalOrderHash,
            buyer: msg.sender,
            totalAmount: _amount,
            status: OrderStatus.Created,
            paymentRecorded: false,
            createdAt: block.timestamp,
            updatedAt: block.timestamp,
            orderNumber: _orderNumber
        });

        escrows[orderCount] = PaymentEscrow({
            orderId: orderCount,
            buyer: msg.sender,
            amount: _amount,
            released: false,
            refunded: false
        });

        orderIdByHash[_externalOrderHash] = orderCount;

        emit OrderCreated(orderCount, msg.sender, _externalOrderHash, _amount, _orderNumber);
        return orderCount;
    }

    /// @notice Mengembalikan seluruh data order.
    /// @param _orderId ID order.
    /// @return Data order lengkap.
    function getOrder(uint256 _orderId) external view orderExists(_orderId) returns (Order memory) {
        return orders[_orderId];
    }

    /// @notice Alias kompatibilitas untuk frontend lama.
    /// @param _orderId ID order.
    /// @return Data order lengkap.
    function getOrderInfo(uint256 _orderId) external view orderExists(_orderId) returns (Order memory) {
        return orders[_orderId];
    }

    /// @notice Mengembalikan status order saja.
    /// @param _orderId ID order.
    /// @return Status order saat ini.
    function getOrderStatus(uint256 _orderId) external view orderExists(_orderId) returns (OrderStatus) {
        return orders[_orderId].status;
    }

    /// @notice Mencari ID order berdasarkan hash off-chain. Mengembalikan 0 bila belum ada.
    /// @param _externalOrderHash Hash kanonik order.
    /// @return ID order atau 0.
    function getOrderIdByHash(bytes32 _externalOrderHash) external view returns (uint256) {
        return orderIdByHash[_externalOrderHash];
    }

    /// @notice Mengecek apakah sebuah akun berwenang sebagai operator.
    /// @param _account Alamat yang dicek.
    /// @return true bila akun adalah owner atau operator aktif.
    function isOperator(address _account) external view returns (bool) {
        return _account == owner || operators[_account];
    }

    /// @notice Mencatat pembayaran dan mengubah status menjadi Paid.
    /// @param _orderId ID order.
    function recordPayment(uint256 _orderId) external onlyOperator orderExists(_orderId) {
        Order storage order = orders[_orderId];
        PaymentEscrow storage escrow = escrows[_orderId];

        require(!order.paymentRecorded, "Payment already recorded");
        require(
            order.status == OrderStatus.Created || order.status == OrderStatus.AwaitingPayment,
            "Invalid state transition"
        );
        require(escrow.amount > 0 && escrow.amount == order.totalAmount, "Incorrect amount");

        order.paymentRecorded = true;
        emit PaymentRecorded(_orderId, msg.sender, escrow.amount);
        _setStatus(_orderId, OrderStatus.Paid);
    }

    /// @notice Mengubah status untuk tahap produksi dan kesiapan kirim.
    /// @dev Paid/Shipped tidak boleh lewat fungsi ini; gunakan recordPayment/shipOrder.
    /// @param _orderId ID order.
    /// @param _newStatus Hanya AwaitingPayment, InProduction, atau ReadyToShip.
    function updateOrderStatus(
        uint256 _orderId,
        OrderStatus _newStatus
    ) external onlyOperator orderExists(_orderId) notFinal(_orderId) {
        require(
            _newStatus == OrderStatus.AwaitingPayment ||
                _newStatus == OrderStatus.InProduction ||
                _newStatus == OrderStatus.ReadyToShip,
            "Invalid state transition"
        );
        require(_isValidTransition(orders[_orderId].status, _newStatus), "Invalid state transition");

        _setStatus(_orderId, _newStatus);
    }

    /// @notice Menandai order sudah dikirim. Hanya dari ReadyToShip.
    /// @param _orderId ID order.
    function shipOrder(uint256 _orderId) external onlyOperator orderExists(_orderId) {
        require(orders[_orderId].status == OrderStatus.ReadyToShip, "Not ready to ship");

        _setStatus(_orderId, OrderStatus.Shipped);
        emit OrderShipped(_orderId, msg.sender);
    }

    /// @notice Pembeli mengonfirmasi penerimaan; escrow dirilis ke pemilik toko.
    /// @param _orderId ID order.
    function confirmReceived(uint256 _orderId) external orderExists(_orderId) onlyBuyer(_orderId) {
        Order storage order = orders[_orderId];
        PaymentEscrow storage escrow = escrows[_orderId];

        require(order.status == OrderStatus.Shipped, "Not shipped yet");
        require(!escrow.released, "Already released");

        // Effects sebelum interaction untuk mencegah reentrancy.
        escrow.released = true;
        _setStatus(_orderId, OrderStatus.Completed);

        payable(owner).transfer(escrow.amount);

        emit OrderCompleted(_orderId, msg.sender);
        emit EscrowReleased(_orderId, owner, escrow.amount);
    }

    /// @notice Membatalkan order dan mengembalikan escrow ke pembeli bila ada.
    /// @dev Aturan aktor per state: Created/AwaitingPayment oleh buyer atau operator;
    ///      Paid/InProduction/ReadyToShip hanya operator; Shipped tidak dapat dibatalkan.
    /// @param _orderId ID order.
    /// @param _reason Alasan pembatalan, maksimum 64 byte.
    function cancelOrder(
        uint256 _orderId,
        string calldata _reason
    ) external orderExists(_orderId) notFinal(_orderId) {
        Order storage order = orders[_orderId];
        PaymentEscrow storage escrow = escrows[_orderId];
        OrderStatus current = order.status;

        require(bytes(_reason).length <= 64, "Reason too long");

        if (current == OrderStatus.Created || current == OrderStatus.AwaitingPayment) {
            require(
                msg.sender == order.buyer || msg.sender == owner || operators[msg.sender],
                "Not authorized to cancel"
            );
        } else if (
            current == OrderStatus.Paid ||
            current == OrderStatus.InProduction ||
            current == OrderStatus.ReadyToShip
        ) {
            require(msg.sender == owner || operators[msg.sender], "Not authorized to cancel");
        } else {
            revert("Invalid state transition");
        }

        // Effects sebelum interaction: status dan refunded dikunci lebih dulu.
        _setStatus(_orderId, OrderStatus.Cancelled);

        if (escrow.amount > 0 && !escrow.released && !escrow.refunded) {
            escrow.refunded = true;
            payable(escrow.buyer).transfer(escrow.amount);
            emit EscrowRefunded(_orderId, escrow.buyer, escrow.amount);
        }

        emit OrderCancelled(_orderId, msg.sender, _reason);
    }

    /// @notice Menambahkan akun sebagai operator.
    /// @param _account Alamat operator baru, tidak boleh alamat nol.
    function addOperator(address _account) external onlyOwner {
        require(_account != address(0), "Invalid operator");
        require(!operators[_account], "Operator already active");

        operators[_account] = true;
        emit OperatorUpdated(_account, true);
    }

    /// @notice Menonaktifkan operator. Owner tidak dapat dihapus.
    /// @param _account Alamat operator yang dinonaktifkan.
    function removeOperator(address _account) external onlyOwner {
        require(_account != owner, "Cannot remove owner");
        require(operators[_account], "Operator not active");

        operators[_account] = false;
        emit OperatorUpdated(_account, false);
    }

    /// @dev Tabel transisi maju. Cancel tidak lewat sini (V2).
    function _isValidTransition(OrderStatus _from, OrderStatus _to) internal pure returns (bool) {
        if (_from == OrderStatus.Created) {
            return _to == OrderStatus.AwaitingPayment || _to == OrderStatus.Paid;
        }
        if (_from == OrderStatus.AwaitingPayment) {
            return _to == OrderStatus.Paid;
        }
        if (_from == OrderStatus.Paid) {
            return _to == OrderStatus.InProduction;
        }
        if (_from == OrderStatus.InProduction) {
            return _to == OrderStatus.ReadyToShip;
        }
        if (_from == OrderStatus.ReadyToShip) {
            return _to == OrderStatus.Shipped;
        }
        return false;
    }

    /// @dev Mengubah status dan memancarkan OrderStatusUpdated.
    function _setStatus(uint256 _orderId, OrderStatus _newStatus) private {
        OrderStatus oldStatus = orders[_orderId].status;
        orders[_orderId].status = _newStatus;
        orders[_orderId].updatedAt = block.timestamp;
        emit OrderStatusUpdated(_orderId, oldStatus, _newStatus, msg.sender);
    }
}
