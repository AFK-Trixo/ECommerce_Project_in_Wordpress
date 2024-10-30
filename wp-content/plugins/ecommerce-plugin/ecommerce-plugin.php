<?php
/*
Plugin Name: E-commerce Plugin
Description: Custom e-commerce functionality plugin for WordPress.
Version: 1.0
Author: Your Name
*/

// Plugin code will go here

function start_session() {
    if (!session_id()) {
        session_start();
    }
}
add_action('init', 'start_session', 1);

function ecommerce_enqueue_styles() {
    wp_enqueue_style('ecommerce-style', plugin_dir_url(__FILE__) . 'style.css');
}
add_action('wp_enqueue_scripts', 'ecommerce_enqueue_styles');

function ecommerce_display_products() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'products';

    $brand_filter = isset($_GET['brand']) ? sanitize_text_field($_GET['brand']) : '';
    $category_filter = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
    $price_filter = isset($_GET['price']) ? floatval($_GET['price']) : 0;

    $query = "SELECT * FROM $table_name WHERE 1=1";
    if ($brand_filter) {
        $query .= $wpdb->prepare(" AND brand = %s", $brand_filter);
    }
    if ($category_filter) {
        $query .= $wpdb->prepare(" AND category = %s", $category_filter);
    }
    if ($price_filter > 0) {
        $query .= $wpdb->prepare(" AND price <= %f", $price_filter);
    }

    $products = $wpdb->get_results($query);

    ob_start();
    echo '<div class="shop-container">';

    ?>
    <div class="filter-sidebar">
        <h2>Filter Products</h2>
        <form method="GET" class="product-filter-form">
            <label for="brand">Brand:</label>
            <input type="text" name="brand" value="<?php echo esc_attr($brand_filter); ?>" />
            
            <label for="category">Category:</label>
            <input type="text" name="category" value="<?php echo esc_attr($category_filter); ?>" />
            
            <label for="price">Max Price:</label>
            <input type="number" name="price" value="<?php echo esc_attr($price_filter); ?>" />
            
            <label for="rating">Min Rating:</label>
            <input type="number" name="rating" value="4" min="0" max="5" />

            <input type="submit" value="Filter">
        </form>
    </div>
    <?php

    echo '<div class="product-grid">';
    if ($products) {
        foreach ($products as $product) {
            echo '<div class="product-card">';
            echo '<img src="' . esc_url($product->image_url) . '" alt="' . esc_html($product->name) . '" class="product-image">';
            echo '<h3>' . esc_html($product->name) . '</h3>';
            echo '<p><strong>Brand:</strong> ' . esc_html($product->brand) . '</p>';
            echo '<p><strong>Description:</strong> ' . esc_html($product->description) . '</p>';
            echo '<p><strong>Price:</strong> $' . esc_html($product->price) . '</p>';
            echo '<p><strong>Stock:</strong> ' . esc_html($product->stock) . '</p>';
            echo '<p><strong>Category:</strong> ' . esc_html($product->category) . '</p>';
            echo '<p><strong>Rating:</strong> ' . esc_html($product->rating) . '/5</p>';

            echo '<div class="button-container">';

            
            echo '<form method="POST" action="" class="add-to-cart-form">';
            echo '<input type="hidden" name="product_id" value="' . esc_attr($product->id) . '">';
            echo '<label for="quantity">Quantity:</label>';
            echo '<input type="number" name="quantity" value="1" min="1" required />';
            echo '<input type="submit" name="add_to_cart" value="Add to Cart" class="add-to-cart-button">';
            echo '</form>';

            
            display_add_to_wishlist_button($product->id); 

            echo '</div>'; 

            echo '</div>'; 
        }
    } else {
        echo '<p>No products available.</p>';
    }
    echo '</div>'; 

    echo '</div>'; 

    return ob_get_clean();
}
add_shortcode('product_catalog', 'ecommerce_display_products');

function handle_add_to_wishlist() {
    if (isset($_POST['add_to_wishlist'])) {
        $product_id = intval($_POST['wishlist_product_id']);
        $user_id = get_current_user_id();

        global $wpdb;
        $table_name = $wpdb->prefix . 'wishlist';

        
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND product_id = %d", $user_id, $product_id));

        if (!$exists) {
            
            $wpdb->insert(
                $table_name,
                array(
                    'user_id' => $user_id,
                    'product_id' => $product_id,
                ),
                array(
                    '%d',
                    '%d'
                )
            );

            
            wp_redirect(home_url('/wishlist')); 
            exit();
        } else {
            echo '<p>This product is already in your wishlist.</p>';
        }
    }
}
add_action('init', 'handle_add_to_wishlist');

function display_wishlist() {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        global $wpdb;
        $table_name = $wpdb->prefix . 'wishlist';
        $products_table = $wpdb->prefix . 'products'; 

        
        $wishlist_items = $wpdb->get_results($wpdb->prepare("
            SELECT p.id, p.name, p.price, p.image_url
            FROM $table_name w
            JOIN $products_table p ON w.product_id = p.id
            WHERE w.user_id = %d
        ", $user_id));

        
        if ($wishlist_items) {
            ob_start();
            echo '<div class="wishlist-container">';
            echo '<h2>Your Wishlist</h2>';
            foreach ($wishlist_items as $item) {
                echo '<div class="wishlist-item">';
                echo '<img src="' . esc_url($item->image_url) . '" alt="' . esc_html($item->name) . '">';
                echo '<h3>' . esc_html($item->name) . '</h3>';
                echo '<p>Price: $' . esc_html($item->price) . '</p>';
                echo '<form method="POST" action="">';
                echo '<input type="hidden" name="remove_wishlist_product_id" value="' . esc_attr($item->id) . '">';
                echo '<input type="submit" name="remove_from_wishlist" value="Remove from Wishlist">';
                echo '</form>';
                echo '</div>';
            }
            echo '</div>';
            return ob_get_clean();
        } else {
            return '<p>Your wishlist is empty.</p>';
        }
    } else {
        return '<p>You need to log in to view your wishlist.</p>';
    }
}
add_shortcode('wishlist_page', 'display_wishlist');

function handle_remove_from_wishlist() {
    if (isset($_POST['remove_from_wishlist'])) {
        $product_id = intval($_POST['remove_wishlist_product_id']);
        $user_id = get_current_user_id();

        global $wpdb;
        $table_name = $wpdb->prefix . 'wishlist';

        
        $wpdb->delete(
            $table_name,
            array(
                'user_id' => $user_id,
                'product_id' => $product_id
            ),
            array('%d', '%d')
        );

        
        wp_redirect(home_url('/wishlist')); 
        exit();
    }
}
add_action('init', 'handle_remove_from_wishlist');



function add_to_cart() {
    if (isset($_POST['add_to_cart'])) {
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'products';
        $product = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $product_id));
        
        if ($product) {
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = array();
            }
            
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$product_id] = array(
                    'name' => $product->name,
                    'price' => $product->price,
                    'quantity' => $quantity,
                    'total' => $product->price * $quantity
                );
            }
        }
        
        
        wp_redirect(home_url('/cart'));
        exit;
    }
}
add_action('init', 'add_to_cart');


function display_cart() {
    ob_start();
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        echo '<h2>Your Cart</h2>';
        echo '<table class="cart-table">';
        echo '<tr><th>Product</th><th>Price</th><th>Quantity</th><th>Total</th><th>Action</th></tr>';
        
        foreach ($_SESSION['cart'] as $product_id => $details) {
            echo '<tr>';
            echo '<td>' . esc_html($details['name']) . '</td>';
            echo '<td>$' . esc_html($details['price']) . '</td>';
            
            
            echo '<td>';
            echo '<form method="POST" action="">';
            echo '<input type="hidden" name="update_product_id" value="' . esc_attr($product_id) . '">';
            echo '<input type="number" name="quantity" value="' . esc_attr($details['quantity']) . '" min="1">';
            echo '<input type="submit" name="update_quantity" value="Update">';
            echo '</form>';
            echo '</td>';
            
            echo '<td>$' . esc_html($details['total']) . '</td>';
            
            
            echo '<td>';
            echo '<form method="POST" action="">';
            echo '<input type="hidden" name="remove_product_id" value="' . esc_attr($product_id) . '">';
            echo '<input type="submit" name="remove_from_cart" value="Remove">';
            echo '</form>';
            echo '</td>';
            
            echo '</tr>';
        }
        echo '</table>';
        echo '<a href="checkout">Proceed to Checkout</a>';
    } else {
        echo '<p>Your cart is empty.</p>';
    }
    return ob_get_clean();
}
add_shortcode('cart_page', 'display_cart');


function update_cart_quantity() {
    if (isset($_POST['update_quantity'])) {
        $product_id = intval($_POST['update_product_id']);
        $quantity = intval($_POST['quantity']);
        
        if (isset($_SESSION['cart'][$product_id]) && $quantity > 0) {
            $_SESSION['cart'][$product_id]['quantity'] = $quantity;
            $_SESSION['cart'][$product_id]['total'] = $_SESSION['cart'][$product_id]['price'] * $quantity;
        }
        
        wp_redirect(home_url('/cart'));
        exit;
    }
}
add_action('init', 'update_cart_quantity');


function remove_from_cart() {
    if (isset($_POST['remove_from_cart'])) {
        $product_id = intval($_POST['remove_product_id']);
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
        }
        
        
        wp_redirect(home_url('/cart'));
        exit;
    }
}
add_action('init', 'remove_from_cart');



function display_checkout() {
    ob_start();
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        $total = 0;
        foreach ($_SESSION['cart'] as $details) {
            $total += $details['total'];
        }
        $tax = $total * 0.10; 
        $shipping = 20; 
        $grand_total = $total + $tax + $shipping;

        ?>
        <h2>Checkout</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="address">Shipping Address:</label>
                <input type="text" name="address" required>
            </div>
            <div class="form-group">
                <label for="shipping_method">Select Shipping Method:</label>
                <select name="shipping_method" onchange="updateShippingCost(this.value)">
                    <option value="standard" data-cost="20">Standard Shipping – $20.00</option>
                    <option value="express" data-cost="50">Express Shipping – $50.00</option>
                </select>
            </div>

            <div class="order-summary">
                <h3>Order Summary</h3>
                <p>Total: $<?php echo number_format($total, 2); ?></p>
                <p>Tax: $<?php echo number_format($tax, 2); ?></p>
                <p>Shipping: $<span id="shipping-cost"><?php echo number_format($shipping, 2); ?></span></p>
                <p>Grand Total: $<span id="grand-total"><?php echo number_format($grand_total, 2); ?></span></p>
            </div>

            <input type="hidden" id="grand-total-input" name="grand_total" value="<?php echo esc_attr($grand_total); ?>">
            <input type="submit" name="complete_checkout" value="Complete Checkout">
        </form>

        <script>
            function updateShippingCost(shippingMethod) {
                const total = <?php echo json_encode($total); ?>;
                const tax = <?php echo json_encode($tax); ?>;
                let shippingCost = 20;

                if (shippingMethod === 'express') {
                    shippingCost = 50;
                }

                document.getElementById('shipping-cost').innerText = shippingCost.toFixed(2);
                const grandTotal = total + tax + shippingCost;
                document.getElementById('grand-total').innerText = grandTotal.toFixed(2);
                document.getElementById('grand-total-input').value = grandTotal.toFixed(2);
            }
        </script>

        <?php
    } else {
        echo '<p>Your cart is empty.</p>';
    }
    return ob_get_clean();
}
add_shortcode('checkout_page', 'display_checkout');

function process_checkout() {
    if (isset($_POST['complete_checkout'])) {
        
        $shipping_address = sanitize_text_field($_POST['address']);
        $shipping_method = sanitize_text_field($_POST['shipping_method']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'orders';
        $current_user = wp_get_current_user();

        if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $product_id => $details) {
                
                $total = $details['quantity'] * $details['price'];
                
                
                $wpdb->insert($table_name, array(
                    'user_id' => $current_user->ID,
                    'product_name' => $details['name'],
                    'product_price' => $details['price'],
                    'quantity' => $details['quantity'],
                    'total' => $total,
                    'shipping_address' => $shipping_address,
                    'shipping_method' => $shipping_method,
                    'grand_total' => $_POST['grand_total']
                ));
            }

            
            $to = $current_user->user_email;
            $subject = 'Order Confirmation - BestLaptops';
            $headers = array('Content-Type: text/html; charset=UTF-8');
            $message = "<h1>Order Confirmation</h1>";
            $message .= "<p>Thank you for your order! Here are the details:</p>";
            $message .= "<strong>Shipping Address:</strong> " . esc_html($shipping_address) . "<br/>";
            $message .= "<strong>Shipping Method:</strong> " . esc_html($shipping_method) . "<br/>";
            $message .= "<h2>Order Summary:</h2>";

            foreach ($_SESSION['cart'] as $details) {
                $message .= "<p><strong>Product:</strong> " . esc_html($details['name']) . "<br/>";
                $message .= "<strong>Price:</strong> $" . esc_html(number_format($details['price'], 2)) . "<br/>";
                $message .= "<strong>Quantity:</strong> " . esc_html($details['quantity']) . "<br/>";
                $message .= "<strong>Total:</strong> $" . esc_html(number_format($details['price'] * $details['quantity'], 2)) . "</p><hr/>";
            }

            $message .= "<p><strong>Grand Total:</strong> $" . esc_html(number_format($_POST['grand_total'], 2)) . "</p>";

           
            wp_mail($to, $subject, $message, $headers);

            
            unset($_SESSION['cart']);

            
            wp_redirect(home_url('/order-confirmation'));
            exit;
        }
    }
}
add_action('init', 'process_checkout');

function display_order_confirmation() {
    ob_start();
    echo '<h2>Order Confirmation</h2>';
    echo '<p>Thank you for your order! A confirmation email has been sent to you.</p>';
    return ob_get_clean();
}
add_shortcode('order_confirmation_page', 'display_order_confirmation');




// Handle user registration
function handle_user_registration() {
    if (isset($_POST['register'])) {
        
        $email = sanitize_email($_POST['email']);
        $password = sanitize_text_field($_POST['password']);

        
        if (!is_email($email)) {
            echo '<p class="error-message">Please enter a valid email address.</p>';
            return;
        }

       
        if (email_exists($email)) {
            echo '<p class="error-message">This email is already registered. Please try a different email.</p>';
            return;
        }

        
        if (strlen($password) < 6) {
            echo '<p class="error-message">Password must be at least 6 characters long.</p>';
            return;
        }

        $user_id = wp_create_user($email, $password, $email);

        if (is_wp_error($user_id)) {
            echo '<p class="error-message">' . $user_id->get_error_message() . '</p>';
        } else {
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
            wp_redirect(home_url('/profile')); 
            exit();
        }
    }
}
add_action('init', 'handle_user_registration');


function user_registration_form() {
    ob_start();
    ?>
    <div class="registration-container">
        <h2>User Registration</h2>
        <form method="POST" class="registration-form">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <input type="submit" name="register" value="Register">
            </div>
        </form>
    </div>
    <?php
    
    handle_user_registration();

    return ob_get_clean();
}
add_shortcode('custom_registration', 'user_registration_form');


function handle_user_login() {
    if (isset($_POST['login'])) {
        $email = sanitize_email($_POST['email']);
        $password = sanitize_text_field($_POST['password']);

        $creds = array(
            'user_login' => $email,
            'user_password' => $password,
            'remember' => true
        );
        $user = wp_signon($creds, false);

        if (is_wp_error($user)) {
            echo '<p class="error-message">' . $user->get_error_message() . '</p>';
        } else {
            wp_redirect(home_url('/profile'));
            exit();
        }
    }
}
add_action('init', 'handle_user_login');


function user_login_form() {
    ob_start();
    ?>
    <div class="login-container">
        <h2>User Login</h2>
        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <input type="submit" name="login" value="Login">
            </div>
        </form>
    </div>
    <?php
    handle_user_login();

    return ob_get_clean();
}
add_shortcode('custom_login', 'user_login_form');


function handle_profile_update() {
    if (is_user_logged_in() && isset($_POST['update_profile'])) {
        $current_user = wp_get_current_user();
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $new_password = sanitize_text_field($_POST['new_password']);
        wp_update_user([
            'ID' => $current_user->ID,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'user_email' => $email
        ]);
        if (!empty($new_password)) {
            wp_set_password($new_password, $current_user->ID);
        }
        echo '<p class="success-message">Profile updated successfully!</p>';
    }
}
add_action('init', 'handle_profile_update');


function get_user_orders($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'orders';
    $query = $wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d ORDER BY order_date DESC", $user_id);
    return $wpdb->get_results($query);
}


function user_profile_form() {
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        ob_start();
        ?>
        <div class="profile-container">
            <h2>Your Profile</h2>
            <form method="POST" class="profile-form">
                <div class="form-group">
                    <label for="first_name">First Name:</label>
                    <input type="text" name="first_name" value="<?php echo esc_attr($current_user->first_name); ?>" />
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name:</label>
                    <input type="text" name="last_name" value="<?php echo esc_attr($current_user->last_name); ?>" />
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" name="email" value="<?php echo esc_attr($current_user->user_email); ?>" required />
                </div>
                <div class="form-group">
                    <label for="new_password">New Password (leave blank to keep current):</label>
                    <input type="password" name="new_password" />
                </div>
                <div class="form-group">
                    <input type="submit" name="update_profile" value="Update Profile" />
                </div>
            </form>
            
            <h2>Your Past Orders</h2>
            <?php
            
            $orders = get_user_orders($current_user->ID);
            if ($orders) {
                echo '<table class="orders-table">';
                echo '<thead><tr><th>Product Name</th><th>Price</th><th>Quantity</th><th>Total</th><th>Order Date</th></tr></thead>';
                echo '<tbody>';
                foreach ($orders as $order) {
                    echo '<tr>';
                    echo '<td>' . esc_html($order->product_name) . '</td>';
                    echo '<td>$' . esc_html(number_format($order->product_price, 2)) . '</td>';
                    echo '<td>' . esc_html($order->quantity) . '</td>';
                    echo '<td>$' . esc_html(number_format($order->total, 2)) . '</td>';
                    echo '<td>' . esc_html(date('Y-m-d H:i', strtotime($order->order_date))) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<p>No past orders found.</p>';
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    } else {
        return '<p>You need to log in to view this page.</p>';
    }
}
add_shortcode('custom_profile', 'user_profile_form');

function display_add_to_wishlist_button($product_id) {
    if (is_user_logged_in()) {
        echo '<form method="POST" action="">
                <input type="hidden" name="wishlist_product_id" value="' . esc_attr($product_id) . '">
                <input type="submit" name="add_to_wishlist" value="Add to Wishlist" class="wishlist-button">
              </form>';
    } else {
        echo '<p><a href="' . wp_login_url() . '">Log in to add to wishlist</a></p>';
    }
}


add_action('admin_menu', 'ecommerce_admin_menu');

function ecommerce_admin_menu() {
    
    add_menu_page('Product Management', 'Products', 'manage_options', 'product-management', 'ecommerce_product_management_page', 'dashicons-products', 20);

    
    add_menu_page('Order Management', 'Orders', 'manage_options', 'order-management', 'ecommerce_order_management_page', 'dashicons-cart', 21);
    
    add_menu_page('User Management', 'Users', 'manage_options', 'user-management', 'ecommerce_user_management_page', 'dashicons-admin-users', 22);
}


function ecommerce_product_management_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'products';
    
    if (isset($_GET['action'])) {
        
        if ($_GET['action'] == 'add') {
            ecommerce_add_product_page();
        } elseif ($_GET['action'] == 'edit' && isset($_GET['product_id'])) {
            ecommerce_edit_product_page($_GET['product_id']);
        } elseif ($_GET['action'] == 'delete' && isset($_GET['product_id'])) {
            ecommerce_delete_product($_GET['product_id']);
        }
    } else {
        
        $products = $wpdb->get_results("SELECT * FROM $table_name");
        ?>
        <div class="wrap">
            <h1>Product Management</h1>
            <a href="?page=product-management&action=add" class="button button-primary">Add New Product</a>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product) { ?>
                        <tr>
                            <td><?php echo esc_html($product->id); ?></td>
                            <td><?php echo esc_html($product->name); ?></td>
                            <td><?php echo esc_html($product->category); ?></td>
                            <td><?php echo esc_html($product->price); ?></td>
                            <td><?php echo esc_html($product->stock); ?></td>
                            <td>
                                <a href="?page=product-management&action=edit&product_id=<?php echo esc_attr($product->id); ?>">Edit</a> |
                                <a href="?page=product-management&action=delete&product_id=<?php echo esc_attr($product->id); ?>">Delete</a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}

function ecommerce_add_product_page() {
    global $wpdb;
    
    if (isset($_POST['add_product'])) {
        $wpdb->insert(
            $wpdb->prefix . 'products',
            array(
                'name' => sanitize_text_field($_POST['name']),
                'category' => sanitize_text_field($_POST['category']),
                'price' => floatval($_POST['price']),
                'stock' => intval($_POST['stock']),
                'description' => sanitize_textarea_field($_POST['description']),
                'image_url' => esc_url($_POST['image_url']),
            )
        );
        echo '<div class="updated"><p>Product added successfully!</p></div>';
    }
    
  
    ?>
    <div class="wrap">
        <h1>Add New Product</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="name">Product Name</label></th>
                    <td><input type="text" name="name" required /></td>
                </tr>
                <tr>
                    <th><label for="category">Category</label></th>
                    <td><input type="text" name="category" required /></td>
                </tr>
                <tr>
                    <th><label for="price">Price</label></th>
                    <td><input type="number" name="price" step="0.01" required /></td>
                </tr>
                <tr>
                    <th><label for="stock">Stock</label></th>
                    <td><input type="number" name="stock" required /></td>
                </tr>
                <tr>
                    <th><label for="description">Description</label></th>
                    <td><textarea name="description"></textarea></td>
                </tr>
                <tr>
                    <th><label for="image_url">Image URL</label></th>
                    <td><input type="text" name="image_url" /></td>
                </tr>
            </table>
            <input type="submit" name="add_product" value="Add Product" class="button button-primary" />
        </form>
    </div>
    <?php
}


function ecommerce_edit_product_page($product_id) {
    global $wpdb;
    $product = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}products WHERE id = $product_id");

    if (isset($_POST['update_product'])) {
        $wpdb->update(
            $wpdb->prefix . 'products',
            array(
                'name' => sanitize_text_field($_POST['name']),
                'category' => sanitize_text_field($_POST['category']),
                'price' => floatval($_POST['price']),
                'stock' => intval($_POST['stock']),
                'description' => sanitize_textarea_field($_POST['description']),
                'image_url' => esc_url($_POST['image_url']),
            ),
            array('id' => $product_id)
        );
        echo '<div class="updated"><p>Product updated successfully!</p></div>';
    }

    
    ?>
    <div class="wrap">
        <h1>Edit Product</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="name">Product Name</label></th>
                    <td><input type="text" name="name" value="<?php echo esc_attr($product->name); ?>" required /></td>
                </tr>
                <tr>
                    <th><label for="category">Category</label></th>
                    <td><input type="text" name="category" value="<?php echo esc_attr($product->category); ?>" required /></td>
                </tr>
                <tr>
                    <th><label for="price">Price</label></th>
                    <td><input type="number" name="price" value="<?php echo esc_attr($product->price); ?>" step="0.01" required /></td>
                </tr>
                <tr>
                    <th><label for="stock">Stock</label></th>
                    <td><input type="number" name="stock" value="<?php echo esc_attr($product->stock); ?>" required /></td>
                </tr>
                <tr>
                    <th><label for="description">Description</label></th>
                    <td><textarea name="description"><?php echo esc_textarea($product->description); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="image_url">Image URL</label></th>
                    <td><input type="text" name="image_url" value="<?php echo esc_url($product->image_url); ?>" /></td>
                </tr>
            </table>
            <input type="submit" name="update_product" value="Update Product" class="button button-primary" />
        </form>
    </div>
    <?php
}


function ecommerce_delete_product($product_id) {
    global $wpdb;
    $wpdb->delete("{$wpdb->prefix}products", array('id' => $product_id));
    echo '<div class="updated"><p>Product deleted successfully!</p></div>';
}


function ecommerce_order_management_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'orders';
    $orders = $wpdb->get_results("SELECT * FROM $table_name");

    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['order_id'])) {
        
        ecommerce_edit_order_page(intval($_GET['order_id']));
    } else {
        
        ?>
        <div class="wrap">
            <h1>Order Management</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>User ID</th>
                        <th>Product</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order) { ?>
                        <tr>
                            <td><?php echo esc_html($order->id); ?></td>
                            <td><?php echo esc_html($order->user_id); ?></td>
                            <td><?php echo esc_html($order->product_name); ?></td>
                            <td><?php echo esc_html($order->status); ?></td>
                            <td><?php echo esc_html($order->total); ?></td>
                            <td>
                                <a href="?page=order-management&action=edit&order_id=<?php echo esc_attr($order->id); ?>">Edit</a> |
                                <a href="?page=order-management&action=delete&order_id=<?php echo esc_attr($order->id); ?>">Delete</a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}


function ecommerce_edit_order_page($order_id) {
    global $wpdb;

    
    $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}orders WHERE id = %d", $order_id));

    if (!$order) {
        echo '<div class="error"><p>Order not found.</p></div>';
        return;
    }

    if (isset($_POST['save_order'])) {
        
        $status = sanitize_text_field($_POST['status']);
        $total = floatval($_POST['total']);

        
        $wpdb->update(
            $wpdb->prefix . 'orders',
            array(
                'status' => $status,
                'total'  => $total,
            ),
            array('id' => $order_id),
            array('%s', '%f'),
            array('%d')
        );

        
        ob_start();
        wp_redirect(admin_url('admin.php?page=order-management'));
        exit;
    }

    
    
    ?>
    <div class="wrap">
        <h1>Edit Order #<?php echo esc_html($order->id); ?></h1>
        <form method="POST">
            <table class="form-table">
                <tr>
                    <th><label for="status">Order Status</label></th>
                    <td>
                        <select name="status" id="status">
                            <option value="processing" <?php selected($order->status, 'processing'); ?>>Processing</option>
                            <option value="shipped" <?php selected($order->status, 'shipped'); ?>>Shipped</option>
                            <option value="delivered" <?php selected($order->status, 'delivered'); ?>>Delivered</option>
                            <option value="cancelled" <?php selected($order->status, 'cancelled'); ?>>Cancelled</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="total">Order Total</label></th>
                    <td><input type="text" name="total" value="<?php echo esc_attr($order->total); ?>" /></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="save_order" class="button button-primary" value="Save Changes">
            </p>
        </form>
    </div>
    <?php
}


function ecommerce_user_management_page() {
    global $wpdb;
    $users = get_users();

    ?>
    <div class="wrap">
        <h1>User Management</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user) { ?>
                    <tr>
                        <td><?php echo esc_html($user->ID); ?></td>
                        <td><?php echo esc_html($user->user_login); ?></td>
                        <td><?php echo esc_html($user->user_email); ?></td>
                        <td><?php echo esc_html(implode(', ', $user->roles)); ?></td>
                        <td>
                            <a href="user-edit.php?user_id=<?php echo esc_attr($user->ID); ?>">Edit</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php
}

?>
