// SPDX-License-Identifier: MIT
pragma solidity ^0.8.19;
contract HSportStore {
    enum OrderStatus { Created, Paid, Shipped, Completed }
    struct Order {
        uint256 id;
        address buyer;
        uint256 totalAmount;
        OrderStatus status;
        uint256 createdAt;
        uint256 updatedAt;
        string orderNumber;
    }
    struct PaymentEscrow {
        uint256 orderId;
        address buyer;
        uint256 amount;
        bool released;
    }
    address public owner;
    uint256 public orderCount;
    mapping(uint256 => Order) public orders;
    mapping(uint256 => PaymentEscrow) public escrows;
    event OrderCreated(uint256 indexed orderId, address indexed buyer, uint256 amount, string orderNumber);
    event OrderShipped(uint256 indexed orderId);
    event OrderCompleted(uint256 indexed orderId);
    event EscrowReleased(uint256 indexed orderId, address seller);
    modifier onlyOwner() { require(msg.sender == owner, "Not owner"); _; }
    modifier onlyBuyer(uint256 _orderId) { require(orders[_orderId].buyer == msg.sender, "Not buyer"); _; }
    modifier orderExists(uint256 _orderId) { require(_orderId > 0 && _orderId <= orderCount, "Order not found"); _; }
    constructor() {
        owner = msg.sender;
    }
    function createOrder(string memory _orderNumber, uint256 _amount) external payable returns (uint256) {
        require(msg.value == _amount, "Incorrect amount");
        require(_amount > 0, "Amount must be > 0");
        orderCount++;
        orders[orderCount] = Order(orderCount, msg.sender, _amount, OrderStatus.Paid, block.timestamp, block.timestamp, _orderNumber);
        escrows[orderCount] = PaymentEscrow(orderCount, msg.sender, _amount, false);
        emit OrderCreated(orderCount, msg.sender, _amount, _orderNumber);
        return orderCount;
    }
    function shipOrder(uint256 _orderId) external onlyOwner orderExists(_orderId) {
        require(orders[_orderId].status == OrderStatus.Paid, "Not paid yet");
        orders[_orderId].status = OrderStatus.Shipped;
        orders[_orderId].updatedAt = block.timestamp;
        emit OrderShipped(_orderId);
    }
    function confirmReceived(uint256 _orderId) external onlyBuyer(_orderId) orderExists(_orderId) {
        require(orders[_orderId].status == OrderStatus.Shipped, "Not shipped yet");
        require(!escrows[_orderId].released, "Already released");
        orders[_orderId].status = OrderStatus.Completed;
        orders[_orderId].updatedAt = block.timestamp;
        escrows[_orderId].released = true;
        payable(owner).transfer(escrows[_orderId].amount);
        emit OrderCompleted(_orderId);
        emit EscrowReleased(_orderId, owner);
    }
    function getOrderStatus(uint256 _orderId) external view returns (OrderStatus) {
        return orders[_orderId].status;
    }
    function getOrderInfo(uint256 _orderId) external view returns (Order memory) {
        return orders[_orderId];
    }
}