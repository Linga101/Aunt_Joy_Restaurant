# Aunt Joy's Restaurant - Database Seeders

This directory contains comprehensive seeders for testing all interfaces of Aunt Joy's Restaurant application.

## Files Overview

- **`schema.sql`** - Basic database structure and initial data
- **`schema_with_inventory.sql`** - Database structure with inventory management
- **`seeders.sql`** - Basic test data for all user roles and sample orders
- **`seeders_with_inventory.sql`** - Test data with realistic inventory levels and stock tracking
- **`inventory_functions.sql`** - Stored procedures and triggers for inventory-driven availability
- **`setup_database.sh`** - Linux/macOS setup script (uses inventory-enabled version)
- **`setup_database.bat`** - Windows setup script (uses inventory-enabled version)

## Quick Setup

### Windows (with XAMPP/WAMP)
```batch
setup_database.bat
```

### Linux/macOS
```bash
chmod +x setup_database.sh
./setup_database.sh
```

### Manual Setup
```sql
-- Import schema first
mysql -u root aunt_joys_restaurant < database/schema_with_inventory.sql

-- Then import seeders with inventory
mysql -u root aunt_joys_restaurant < database/seeders_with_inventory.sql

-- Finally, import inventory functions
mysql -u root aunt_joys_restaurant < database/inventory_functions.sql
```

## Test Data Created

### Users (Password: `password123` for all)

#### Customers (4 users)
- **Joyce Chikwanda** - `jchikwanda` / `joyce.chikwanda@email.com`
- **Michael Phiri** - `mphiri` / `mphiri@email.com`
- **Angela Banda** - `mbanda` / `angela.mbanda@email.com`
- **Samuel Kaziwe** - `kaziwe` / `samuel.kaziwe@email.com`

#### Administrators (2 users)
- **System Administrator** - `admin` / `admin@auntjoy.test`
- **Joyce Admin** - `jadmin` / `joyce.admin@auntjoy.test`

#### Sales Personnel (2 users)
- **John Sales** - `jsales` / `john.sales@auntjoy.test`
- **Mary Sales** - `msales` / `mary.sales@auntjoy.test`

#### Managers (2 users)
- **James Manager** - `jmanager` / `james.manager@auntjoy.test`
- **Sarah Manager** - `smanager` / `sarah.manager@auntjoy.test`

### Sample Orders (9 orders)

#### Order Statuses:
- **Pending** (2 orders) - New orders awaiting processing
- **Preparing** (2 orders) - Orders being cooked
- **Out for Delivery** (1 order) - Orders with delivery team
- **Delivered** (3 orders) - Completed orders
- **Cancelled** (1 order) - Cancelled/refunded order

### Additional Test Meals (6 new items)
- Chambo & Nsima (Local Favorites)
- Mixed Grill Platter (International Grill)
- Mango Avocado Salad (Veggie Delights)
- Chocolate Lava Cake (Desserts)
- Samosa Platter (Street Bites)
- Fresh Lemonade (Soups & Sips)

## Testing Scenarios

### Customer Interface Testing
1. **Login** with any customer account
2. **Browse menu** - All meals available
3. **Place orders** - Cart functionality
4. **View order history** - Sample past orders
5. **Track order status** - Real-time updates

### Administrator Testing
1. **Login** with `admin` account
2. **User management** - Create/edit/delete users
3. **Meal management** - Add/edit meals
4. **System overview** - Dashboard statistics
5. **Export reports** - PDF/Excel functionality

### Sales Personnel Testing
1. **Login** with `jsales` or `msales`
2. **Order processing** - Update order statuses
3. **Delivery management** - Track deliveries
4. **Customer communication** - Order updates
5. **Daily sales reports**

### Manager Testing
1. **Login** with `jmanager` or `smanager`
2. **Business analytics** - Sales reports
3. **Performance metrics** - Staff performance
4. **Inventory insights** - Popular meals
5. **Export capabilities** - Advanced reporting

## Features Tested

- ✅ User authentication and role-based access
- ✅ **Inventory-driven meal availability** (meals become unavailable when out of stock)
- ✅ Order lifecycle management
- ✅ Real-time order tracking
- ✅ Payment processing simulation
- ✅ Delivery management
- ✅ Customer order history
- ✅ Admin dashboard statistics
- ✅ Sales personnel workflows
- ✅ Managerial reporting
- ✅ Export functionality (PDF/Excel)
- ✅ **Stock level management and alerts**
- ✅ **Automatic inventory updates when orders are delivered/cancelled**
- ✅ **Order validation based on available stock**

## Database Statistics After Seeding

- **Users**: 10+ test accounts across all roles
- **Orders**: 10 sample orders with various statuses
- **Order Items**: 20+ individual order items
- **Meals**: 14 total meals (8 original + 6 additional)
- **Categories**: 6 meal categories
- **Status History**: Complete order tracking history
- **Inventory Records**: Stock levels for all meals that track inventory
- **Transaction History**: Sample inventory transactions (RESTOCK, SALE, WASTE, ADJUSTMENT)

## Inventory Status After Seeding

### Out of Stock (Unavailable):
- **Mango Avocado Salad** (0 units)
- **Chocolate Lava Cake** (0 units)

### Low Stock (Available but limited):
- **Coconut Cream Cake** (4 units - below min of 8)
- **Mixed Grill Platter** (2 units - below min of 3)

### In Stock (Available):
- **Nsima & Beef Stew** (35 units)
- **Peri-Peri Chicken** (28 units)
- **Tamarind Glazed Wings** (45 units)
- **All other meals** (10+ units each)

### Key Features:
- **Automatic availability updates**: Meals become unavailable when stock reaches 0
- **Stock level alerts**: Low stock items flagged for restocking
- **Order validation**: Orders automatically fail if insufficient stock
- **Real-time updates**: Inventory adjusts when orders are delivered or cancelled
- **Transaction tracking**: Complete history of all inventory movements

## Notes

- All test passwords are `password123`
- Orders include realistic Malawian addresses and phone numbers
- Some meals are marked as unavailable for testing inventory features
- Order timestamps are realistic (last 2-3 days)
- Payment status varies (Pending, Paid, Refunded)

## Resetting Data

To reset test data and start fresh:
1. Drop and recreate the database
2. Run the setup script again
3. Or manually re-run the seeders SQL file

This comprehensive test data allows thorough testing of all Aunt Joy's Restaurant interfaces and workflows!