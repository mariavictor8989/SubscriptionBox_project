<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Subscription.php';
require_once __DIR__ . '/../models/Box.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../config/Database.php';

class UserController extends Controller {

    private User         $userModel;
    private Subscription $subModel;
    private Box          $boxModel;
    private Order        $orderModel;

    public function __construct() {
        $this->userModel  = new User();
        $this->subModel   = new Subscription();
        $this->boxModel   = new Box();
        $this->orderModel = new Order();
    }

    public function dashboard(): void {
        $this->requireLogin();
        $userId       = $_SESSION['user_id'];
        $subscription = $this->subModel->getUserActiveSubscription($userId);
        $db           = Database::getInstance()->getConnection();

        $notif = $db->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0");
        $notif->bind_param("i", $userId);
        $notif->execute();
        $notifCount = $notif->get_result()->fetch_assoc()['cnt'];

        $walletStmt = $db->prepare("SELECT wallet_credit FROM users WHERE id = ?");
        $walletStmt->bind_param("i", $userId);
        $walletStmt->execute();
        $wallet = $walletStmt->get_result()->fetch_assoc()['wallet_credit'];

        $this->render('user/dashboard', [
            'subscription' => $subscription,
            'notifCount'   => $notifCount,
            'wallet'       => $wallet,
        ]);
    }

    public function subscribe(): void {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];
        $error  = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $planId    = (int) $_POST['plan_id'];
            $addressId = (int) $_POST['address_id'];

            $planData = $this->subModel->getPlanById($planId);
            $db       = Database::getInstance()->getConnection();
            $addr     = $db->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ? AND is_serviceable = 1");
            $addr->bind_param("ii", $addressId, $userId);
            $addr->execute();
            $addrData = $addr->get_result()->fetch_assoc();

            if (!$planData)  { $error = 'Invalid plan.'; }
            elseif (!$addrData) { $error = 'Invalid address.'; }
            else {
                $this->subModel->create($userId, $planId);
                $this->userModel->updateTier($userId, $planData['tier']);
                $_SESSION['user_tier'] = $planData['tier'];
                $this->addNotification($userId, 'subscription', "Welcome! Your {$planData['name']} is now active.");
                $this->redirect('index.php?controller=user&action=dashboard&msg=subscribed');
            }
        }

        $plans     = $this->subModel->getPlans();
        $db        = Database::getInstance()->getConnection();
        $addrStmt  = $db->prepare("SELECT * FROM addresses WHERE user_id = ?");
        $addrStmt->bind_param("i", $userId);
        $addrStmt->execute();
        $addresses = $addrStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $this->render('user/subscribe', ['plans' => $plans, 'addresses' => $addresses, 'error' => $error]);
    }

    public function manageSubscription(): void {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];
        $msg    = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $subId  = (int) $_POST['sub_id'];

            if ($action === 'pause') {
                $this->subModel->pause($subId, $userId);
                $msg = 'Subscription paused.';
            } elseif ($action === 'resume') {
                $this->subModel->resume($subId, $userId);
                $msg = 'Subscription resumed!';
            } elseif ($action === 'cancel') {
                $reason = trim($_POST['cancel_reason'] ?? '');
                $this->subModel->cancel($subId, $userId, $reason);
                $msg = 'Subscription cancelled.';
            }
        }

        $subscriptions = $this->subModel->getUserSubscriptions($userId);
        $this->render('user/manage_subscription', ['subscriptions' => $subscriptions, 'msg' => $msg]);
    }

    public function multiBoxes(): void {
        $this->requireLogin();
        $userId        = $_SESSION['user_id'];
        $subscriptions = $this->subModel->getUserSubscriptions($userId);
        $this->render('user/multi_boxes', ['subscriptions' => $subscriptions]);
    }

    public function orderBox(): void {
    $this->requireLogin();
    $userId  = $_SESSION['user_id'];
    $error   = '';
    $success = false;
    $orderId = null;
    $amount  = 0;

    $activeBox   = $this->boxModel->getActiveBox($userId);
    $activeBoxId = $activeBox['id'] ?? null;
    $addonTotal  = $activeBoxId ? $this->boxModel->getAddonTotal($activeBoxId) : 0;
    $addonItems  = $activeBoxId ? array_filter($this->boxModel->getItems($activeBoxId), fn($i) => $i['is_addon']) : [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $subId         = (int) $_POST['subscription_id'];
        $addressId     = (int) $_POST['address_id'];
        $existingBoxId = (int) ($_POST['existing_box_id'] ?? 0);
        
        $cardNumber = trim($_POST['card_number'] ?? '');
        $cvv        = trim($_POST['cvv'] ?? '');
        $expiry     = trim($_POST['expiry'] ?? '');

        $db = Database::getInstance()->getConnection();

        if (empty($cardNumber) || strlen($cardNumber) < 16) {
            $error = 'Please enter a valid 16-digit card number.';
        } elseif (empty($cvv) || strlen($cvv) < 3) {
            $error = 'Invalid CVV code.';
        } else {
            $subStmt = $db->prepare("SELECT us.*, sp.price, sp.name FROM user_subscriptions us JOIN subscription_plans sp ON sp.id = us.plan_id WHERE us.id = ? AND us.user_id = ? AND us.status = 'active'");
            $subStmt->bind_param("ii", $subId, $userId);
            $subStmt->execute();
            $subData = $subStmt->get_result()->fetch_assoc();

            $addrStmt = $db->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ? AND is_serviceable = 1");
            $addrStmt->bind_param("ii", $addressId, $userId);
            $addrStmt->execute();
            $addrData = $addrStmt->get_result()->fetch_assoc();

            if (!$subData) { 
                $error = 'Invalid subscription plan selected.'; 
            } elseif (!$addrData) { 
                $error = 'Please select a valid delivery address.'; 
            } else {
                if ($cvv === '000') {
                    $error = 'Payment Declined: Insufficient funds or invalid card details.';
                } else {
                    $boxId = $existingBoxId > 0 ? $existingBoxId : $this->boxModel->create($subId, $userId);

                    $addonPrice = $this->boxModel->getAddonTotal($boxId);
                    $subtotal   = $subData['price'] + $addonPrice;
                    $tax        = round($subtotal * 0.14, 2);
                    $amount     = round($subtotal + $tax, 2);

                    $orderId = $this->orderModel->create($userId, $boxId, $subId, $addressId, $amount, $tax);
                    $this->orderModel->createShipment($orderId);
                    
                    $this->addNotification($userId, 'subscription', "Payment Successful! Order #$orderId has been placed for " . number_format($amount, 2) . " EGP");
                    $success = true;
                }
            }
        }
    }

    $db = Database::getInstance()->getConnection();
    $subsStmt = $db->prepare("SELECT us.*, sp.name, sp.price FROM user_subscriptions us JOIN subscription_plans sp ON sp.id = us.plan_id WHERE us.user_id = ? AND us.status = 'active'");
    $subsStmt->bind_param("i", $userId);
    $subsStmt->execute();
    $subscriptions = $subsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $addrStmt = $db->prepare("SELECT * FROM addresses WHERE user_id = ? AND is_serviceable = 1");
    $addrStmt->bind_param("i", $userId);
    $addrStmt->execute();
    $addresses = $addrStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $this->render('user/order_box', [
        'success'       => $success,
        'orderId'       => $orderId,
        'amount'        => $amount,
        'error'         => $error,
        'subscriptions' => $subscriptions,
        'addresses'     => $addresses,
        'activeBoxId'   => $activeBoxId,
        'addonItems'    => $addonItems,
        'addonTotal'    => $addonTotal,
    ]);
}

    public function customizeBox(): void {
        $this->requireLogin();
        $userId     = $_SESSION['user_id'];
        $successMsg = '';
        $errorMsg   = '';

        $box      = $this->boxModel->getActiveBox($userId);
        $isLocked = $box ? $this->boxModel->isLocked($box) : false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $box) {
            $action = $_POST['action'] ?? '';

            if ($action === 'set_type') {
                $type = in_array($_POST['box_type'], ['curated','custom']) ? $_POST['box_type'] : 'curated';
                $this->boxModel->updateType($box['id'], $type);
                $this->redirect('index.php?controller=user&action=customizeBox');

            } elseif ($action === 'swap' && !$isLocked) {
                $newItemId    = (int) ($_POST['item_id'] ?? 0);
                $oldBoxItemId = (int) ($_POST['old_box_item_id'] ?? 0);
                $db           = Database::getInstance()->getConnection();

                $itemStmt = $db->prepare("SELECT * FROM items WHERE id = ? AND is_active = 1");
                $itemStmt->bind_param("i", $newItemId);
                $itemStmt->execute();
                $item = $itemStmt->get_result()->fetch_assoc();

                $stockStmt = $db->prepare("SELECT stock_qty FROM inventory WHERE item_id = ?");
                $stockStmt->bind_param("i", $newItemId);
                $stockStmt->execute();
                $stock = $stockStmt->get_result()->fetch_assoc();

                if (!$item)                    { $errorMsg = '❌ Swap rejected: Item not found.'; }
                elseif (($stock['stock_qty'] ?? 0) <= 0) { $errorMsg = '❌ Swap rejected: Out of stock.'; }
                elseif ($_SESSION['user_tier'] !== 'vip' && $item['is_vip_only']) { $errorMsg = '❌ VIP only item.'; }
                elseif ($oldBoxItemId === 0)   { $errorMsg = '⚠️ Select an item to swap from your box.'; }
                else {
                    $this->boxModel->swapItem($oldBoxItemId, $box['id'], $newItemId);
                    $this->boxModel->updateStatus($box['id'], 'customizing');
                    $successMsg = '✅ Swap completed!';
                }

            } elseif ($action === 'add' && !$isLocked) {
                $itemId = (int) ($_POST['item_id'] ?? 0);
                $db     = Database::getInstance()->getConnection();

                $itemStmt = $db->prepare("SELECT * FROM items WHERE id = ? AND is_active = 1");
                $itemStmt->bind_param("i", $itemId);
                $itemStmt->execute();
                $item = $itemStmt->get_result()->fetch_assoc();

                if ($item && $_SESSION['user_tier'] !== 'vip' && $item['is_vip_only']) {
                    $errorMsg = '⭐ VIP members only!';
                } elseif ($item) {
                    $isAddon = isset($_POST['is_addon']);
                    $this->boxModel->addItem($box['id'], $itemId, false, $isAddon);
                    $this->boxModel->updateStatus($box['id'], 'customizing');
                    $successMsg = '✅ Item added!';
                }

            } elseif ($action === 'remove' && !$isLocked) {
                $boxItemId = (int) ($_POST['box_item_id'] ?? 0);
                $this->boxModel->removeItem($boxItemId, $box['id']);
                $successMsg = 'Item removed.';
            }

            $box = $this->boxModel->getActiveBox($userId);
        }

        $db       = Database::getInstance()->getConnection();
        $items    = $db->query("SELECT i.*, inv.stock_qty FROM items i JOIN inventory inv ON inv.item_id = i.id WHERE i.is_active = 1 AND inv.stock_qty > 0 ORDER BY i.name ASC")->fetch_all(MYSQLI_ASSOC);
        $currItems = $box ? $this->boxModel->getItems($box['id']) : [];

        $this->render('user/customize_box', [
            'box'          => $box,
            'isLocked'     => $isLocked,
            'items'        => $items,
            'currentItems' => $currItems,
            'boxType'      => $box['box_type'] ?? 'curated',
            'successMsg'   => $successMsg,
            'errorMsg'     => $errorMsg,
            'tier'         => $_SESSION['user_tier'],
        ]);
    }

    public function preferences(): void {
        $this->requireLogin();
        $userId  = $_SESSION['user_id'];
        $success = '';
        $db      = Database::getInstance()->getConnection();

        $pref = $db->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
        $pref->bind_param("i", $userId);
        $pref->execute();
        $prefs = $pref->get_result()->fetch_assoc();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $allergens = json_encode($_POST['allergens'] ?? []);
            $disliked  = json_encode(array_filter(array_map('trim', explode(',', $_POST['disliked_tags'] ?? ''))));
            $preferred = json_encode(array_filter(array_map('trim', explode(',', $_POST['preferred_tags'] ?? ''))));

            if ($prefs) {
                $upd = $db->prepare("UPDATE user_preferences SET allergens=?, disliked_tags=?, preferred_tags=? WHERE user_id=?");
                $upd->bind_param("sssi", $allergens, $disliked, $preferred, $userId);
                $upd->execute();
            } else {
                $ins = $db->prepare("INSERT INTO user_preferences (user_id, allergens, disliked_tags, preferred_tags) VALUES (?, ?, ?, ?)");
                $ins->bind_param("isss", $userId, $allergens, $disliked, $preferred);
                $ins->execute();
            }
            $success = 'Preferences saved!';
            $prefs = ['allergens' => $allergens, 'disliked_tags' => $disliked, 'preferred_tags' => $preferred];
        }

        $this->render('user/preferences', ['prefs' => $prefs, 'success' => $success]);
    }

    public function addAddress(): void {
        $this->requireLogin();
        $userId  = $_SESSION['user_id'];
        $error   = '';
        $success = '';
        $serviceableRegions = ['Cairo','Giza','Alexandria','Nasr City','Heliopolis','Maadi','Zamalek','6th October','New Cairo'];
        $db = Database::getInstance()->getConnection();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $label   = trim($_POST['label']);
            $address = trim($_POST['full_address']);
            $city    = trim($_POST['city']);
            $region  = trim($_POST['region']);
            $postal  = trim($_POST['postal_code']);

            $isServiceable = (in_array($city, $serviceableRegions) || in_array($region, $serviceableRegions)) ? 1 : 0;

            $countStmt = $db->prepare("SELECT COUNT(*) as cnt FROM addresses WHERE user_id = ?");
            $countStmt->bind_param("i", $userId);
            $countStmt->execute();
            $isDefault = $countStmt->get_result()->fetch_assoc()['cnt'] == 0 ? 1 : 0;

            $ins = $db->prepare("INSERT INTO addresses (user_id, label, full_address, city, region, postal_code, is_serviceable, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->bind_param("isssssii", $userId, $label, $address, $city, $region, $postal, $isServiceable, $isDefault);
            $ins->execute();

            $success = $isServiceable ? '✅ Address added! This area is serviceable.' : '⚠️ Address saved but not in serviceable zone.';
        }

        $addrStmt = $db->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC");
        $addrStmt->bind_param("i", $userId);
        $addrStmt->execute();
        $addresses = $addrStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $this->render('user/add_address', [
            'addresses'          => $addresses,
            'error'              => $error,
            'success'            => $success,
            'serviceableRegions' => $serviceableRegions,
        ]);
    }

    public function notifications(): void {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];
        $db     = Database::getInstance()->getConnection();

        if (isset($_GET['mark_read'])) {
            $upd = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $upd->bind_param("i", $userId);
            $upd->execute();
            $this->redirect('index.php?controller=user&action=notifications');
        }

        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $this->render('user/notifications', ['notifications' => $notifications]);
    }

    public function giftBox(): void {
        $this->requireLogin();
        $userId         = $_SESSION['user_id'];
        $error          = '';
        $success        = '';
        $activationCode = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $recipientEmail = trim($_POST['recipient_email']);
            $planId         = (int) $_POST['plan_id'];
            $planData       = $this->subModel->getPlanById($planId);

            if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email.';
            } elseif (!$planData) {
                $error = 'Invalid plan.';
            } else {
                $activationCode = strtoupper(bin2hex(random_bytes(4)));
                $db  = Database::getInstance()->getConnection();
                $ins = $db->prepare("INSERT INTO gift_subscriptions (payer_id, recipient_email, plan_id, activation_code) VALUES (?, ?, ?, ?)");
                $ins->bind_param("iiss", $userId, $recipientEmail, $planId, $activationCode);
                $ins->execute() ? $success = 'Gift created!' : $error = 'Failed to create gift.';
            }
        }

        $plans = $this->subModel->getPlans();
        $this->render('user/gift_box', [
            'plans'          => $plans,
            'error'          => $error,
            'success'        => $success,
            'activationCode' => $activationCode,
            'recipientEmail' => $_POST['recipient_email'] ?? '',
        ]);
    }

    public function activateGift() {
    $dbInstance = Database::getInstance();
    $conn = $dbInstance->getConnection();

    $userId  = $_SESSION['user_id'];
    $msg     = '';
    $error   = '';
    $success = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $code = strtoupper(trim($_POST['activation_code']));

        $stmt = $conn->prepare("
            SELECT gs.*, sp.name AS plan_name, sp.price, sp.tier
            FROM gift_subscriptions gs
            JOIN subscription_plans sp ON sp.id = gs.plan_id
            WHERE gs.activation_code = ? AND gs.is_activated = 0
        ");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $gift = $stmt->get_result()->fetch_assoc();

        if (!$gift) {
            $error = 'Invalid or already used activation code. Please check and try again.';
        } else {
            $upd = $conn->prepare("UPDATE gift_subscriptions SET is_activated=1, activated_at=NOW() WHERE id=?");
            $upd->bind_param("i", $gift['id']);
            $upd->execute();

            $start       = date('Y-m-d');
            $nextBilling = date('Y-m-d', strtotime('+1 month'));
            $ins = $conn->prepare("
                INSERT INTO user_subscriptions (user_id, plan_id, status, start_date, next_billing)
                VALUES (?, ?, 'gift', ?, ?)
            ");
            $ins->bind_param("iiss", $userId, $gift['plan_id'], $start, $nextBilling);
            $ins->execute();

            $upd2 = $conn->prepare("UPDATE users SET subscription_tier = ? WHERE id = ?");
            $upd2->bind_param("si", $gift['tier'], $userId);
            $upd2->execute();
            $_SESSION['user_tier'] = $gift['tier'];

            $notif    = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'subscription', ?)");
            $notifMsg = "🎁 Your gift subscription ({$gift['plan_name']}) has been activated!";
            $notif->bind_param("is", $userId, $notifMsg);
            $notif->execute();

            $success = true;
            $msg     = "🎉 Gift activated! Your <strong>{$gift['plan_name']}</strong> subscription is now active.";
        }
    }
    $this->render('user/activate_gift', [
    'success' => $success,
    'msg'     => $msg,
    'error'   => $error,
]);
}

    public function shareBox(): void {
        $this->requireLogin();
        $userId  = $_SESSION['user_id'];
        $success = '';
        $db      = Database::getInstance()->getConnection();

        $userStmt = $db->prepare("SELECT referral_code, wallet_credit FROM users WHERE id = ?");
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $userData = $userStmt->get_result()->fetch_assoc();

        if (empty($userData['referral_code'])) {
            $code = strtoupper(substr(md5($userId . time()), 0, 8));
            $upd  = $db->prepare("UPDATE users SET referral_code = ? WHERE id = ?");
            $upd->bind_param("si", $code, $userId);
            $upd->execute();
            $userData['referral_code'] = $code;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['share_box'])) {
            $upd = $db->prepare("UPDATE users SET wallet_credit = wallet_credit + 50 WHERE id = ?");
            $upd->bind_param("i", $userId);
            $upd->execute();
            $this->addNotification($userId, 'reward', '🎉 You earned 50 EGP for sharing your box!');
            $userData['wallet_credit'] += 50;
            $success = '✅ +50 EGP added to your wallet!';
        }

        $boxStmt = $db->prepare("SELECT b.id, sp.name AS plan_name, COUNT(bi.id) AS item_count FROM boxes b JOIN user_subscriptions us ON us.id = b.subscription_id JOIN subscription_plans sp ON sp.id = us.plan_id LEFT JOIN box_items bi ON bi.box_id = b.id WHERE b.user_id = ? AND b.status = 'delivered' GROUP BY b.id ORDER BY b.created_at DESC LIMIT 1");
        $boxStmt->bind_param("i", $userId);
        $boxStmt->execute();
        $latestBox = $boxStmt->get_result()->fetch_assoc();

        $this->render('user/share_box', [
            'userData'  => $userData,
            'latestBox' => $latestBox,
            'success'   => $success,
        ]);
    }

    public function recommendations(): void {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];
        $db     = Database::getInstance()->getConnection();
        $tier   = $_SESSION['user_tier'];

        $pref = $db->prepare("SELECT allergens FROM user_preferences WHERE user_id = ?");
        $pref->bind_param("i", $userId);
        $pref->execute();
        $prefs         = $pref->get_result()->fetch_assoc();
        $userAllergens = json_decode($prefs['allergens'] ?? '[]', true) ?? [];

        $vipFilter = $tier !== 'vip' ? "AND i.is_vip_only = 0" : "";
        $items     = $db->query("SELECT i.*, inv.stock_qty FROM items i JOIN inventory inv ON inv.item_id = i.id WHERE i.is_active = 1 AND inv.stock_qty > 0 $vipFilter ORDER BY i.is_limited DESC LIMIT 12")->fetch_all(MYSQLI_ASSOC);

        $recommendations = array_filter($items, function($item) use ($userAllergens) {
            $itemAllergens = json_decode($item['allergens'] ?? '[]', true) ?? [];
            return empty(array_intersect($userAllergens, $itemAllergens));
        });

        $this->render('user/recommendations', ['recommendations' => array_values($recommendations)]);
    }
}