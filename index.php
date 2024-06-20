<?php
//Init SQLite in memomy
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//Creates table
$db->exec('
    CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL,
        description TEXT,
        price REAL
    )
');

//Get products from DB
function getproducts($db) {
    $stmt = $db->query('SELECT * FROM products');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

//Insert Product in DB
function insertProduct($db, $name, $description, $price) {
    $stmt = $db->prepare('INSERT INTO products (name, description, price) VALUES (?, ?, ?)');
    return $stmt->execute([$name, $description, $price]);
}

//Update Product in DB
function updateProduct($db, $id, $name, $description, $price) {
    $stmt = $db->prepare('UPDATE products SET name = ?, description = ?, price = ? WHERE id = ?');
    return $stmt->execute([$name, $description, $price, $id]);
}

//Exclude Product in DB
function excluirProduct($db, $id) {
    $stmt = $db->prepare('DELETE FROM products WHERE id = ?');
    return $stmt->execute([$id]);
}

//CRUD Actions in DB
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'insert') {
            insertProduct($db, $_POST['name'], $_POST['description'], $_POST['price']);
        } elseif ($_POST['action'] === 'update') {
            updateProduct($db, $_POST['id'], $_POST['name'], $_POST['description'], $_POST['price']);
        } elseif ($_POST['action'] === 'excluir') {
            excluirProduct($db, $_POST['id']);
        }
    }
}

//Get the products list to display in web
$products = getproducts($db);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick and Dirty</title>
    <style>
        * {
        margin: 0;
        padding: 0;
        border: 0;
        font-size: 100%;
        font: inherit;
        vertical-align: baseline;
        text-decoration: none;
        }

        body {
            background: linear-gradient(to right, #161718, #232526);
            font-family: Arial, sans-serif;
            color: #FFF;
            caret-color: #FFF;
        }

        .container {
            margin: 0 10dvw;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        h1 {
            font-size: 2rem;
            margin: 1rem;
        }

        h2 {
            font-size: 1.5rem;
            margin: 1rem;
        }

        input, textarea {
            background-color: #282828;
        }

        button {
            background-color: #181818;
            cursor: pointer;
            margin: 0.4rem 0;
        }

        input, button, textarea {
            border-radius: 8px;
            border: 1px solid #FFF;
            resize: none;
            caret-color: #FFF;
            padding: 0.5rem;
            color: #FFF;
            width: calc(100% - (0.5rem * 2));
        }

        input, button :focus {
            outline: none;
        }

        form {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        label {
            margin: 0 0 0.2rem 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table, th, td {
            border: 1px solid #ccc;
        }

        th, td {
            padding: 0.5rem;
            text-align: left;
        }

        .edit-form {
            display: none;
            margin-bottom: 10px;
        }

        .del-btn {
            background-color: #2e0000;
        }

        .td--actions {
            display: flex;
        }

        .td--actions button:nth-child(1) {
            border-radius: 8px 0px 0px 8px ;
        }

        .td--actions button:nth-child(2) {
            border-radius: 0px 8px 8px 0px ;
        }

        @media only screen and (max-width: 600px) {
            tr {
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Products</h1>
        <div id="edit-form" class="edit-form">
            <h2>Edit Product</h2>
            <form id="form-edit-product">
                <input type="hidden" id="edit-id">
                <label for="edit-name">Name:</label>
                <input type="text" id="edit-name" required>
                <br>
                <label for="edit-description">Description:</label>
                <textarea id="edit-description"></textarea>
                <br>
                <label for="edit-price">Price:</label>
                <input type="number" id="edit-price" step="0.01" required>
                <br>
                <button type="submit">Save Changes</button>
                <button type="button" onclick="cancelarEdicao()">Cancel</button>
            </form>
        </div>

        <form id="form-product">
            <h2>Add New Product</h2>
            <label for="name">Name:</label>
            <input type="text" id="name" placeholder="Insert name" required>
            <br>
            <label for="description">Description:</label>
            <textarea id="description"  placeholder="Insert description"></textarea>
            <br>
            <label for="price">Price:</label>
            <input type="number" id="price" step="0.01"  placeholder="Insert price" required>
            <br>
            <button type="submit">Add New</button>
        </form>


        <h2>Products List</h2>
        <table id="products-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="products-body">
            </tbody>
        </table>
        <button class="del-btn" onclick="deleteLocalStorage()">Delete Local Storage</button>
    </div>
<script>
    //Load products from localStorage and display
    function loadProducts() {
        const productsTable = document.getElementById('products-body');
        productsTable.innerHTML = '';
        let products = JSON.parse(localStorage.getItem('products')) || [];

        //Adds the products in the table
        products.forEach((product, index) => {
            const { id, name, description, price } = product;
            const row = `
                <tr>
                    <td>${id}</td>
                    <td>${name}</td>
                    <td>${description}</td>
                    <td>${price.toFixed(2)}</td>
                    <td class="td--actions">
                        <button onclick="editProduct(${index})">Edit</button>
                        <button class="del-btn" onclick="excluirProduct(${index})">Delete</button>
                    </td>
                </tr>
            `;
            productsTable.innerHTML += row;
        });
    }

    //Adds an new product into HTML
    function addProduct(event) {
        event.preventDefault();

        const name = document.getElementById('name').value;
        const description = document.getElementById('description').value;
        const price = parseFloat(document.getElementById('price').value);

        //Check every input if it is filled
        if (!name || !price) {
            alert('Please, fill the fields.');
            return;
        }

        //Creates a new product as an object
        const novoProduct = {
            id: new Date().getTime(),
            name,
            description,
            price
        };

        //Get the current products from localStorage
        let products = JSON.parse(localStorage.getItem('products')) || [];

        //Adds a new product into the list
        products.push(novoProduct);

        //Save the the list in localStorage
        localStorage.setItem('products', JSON.stringify(products));

        //Clean up the form
        document.getElementById('form-product').reset();

        //Updates the products on display
        loadProducts();
    }

    //Edit a product
    function editProduct(index) {
        const products = JSON.parse(localStorage.getItem('products')) || [];
        const product = products[index];

        //Fills the edit form with the current values
        document.getElementById('edit-id').value = product.id;
        document.getElementById('edit-name').value = product.name;
        document.getElementById('edit-description').value = product.description;
        document.getElementById('edit-price').value = product.price.toFixed(2);

        //Display the edit form and hide add
        document.getElementById('edit-form').style.display = 'block';
        document.getElementById('form-product').style.display = 'none';
    }

    //Saves the edited product changes
    function saveEdit(event) {
        event.preventDefault();

        const id = document.getElementById('edit-id').value;
        const name = document.getElementById('edit-name').value;
        const description = document.getElementById('edit-description').value;
        const price = parseFloat(document.getElementById('edit-price').value);

        //Get the current products from localStorage
        let products = JSON.parse(localStorage.getItem('products')) || [];

        //Find the product in the array by the ID and update
        for (let i = 0; i < products.length; i++) {
            if (products[i].id == id) {
                products[i].name = name;
                products[i].description = description;
                products[i].price = price;
                break;
            }
        }

        //Saves the updated list in localStorage
        localStorage.setItem('products', JSON.stringify(products));

        //Hides the edition form and reloads the products on the table
        document.getElementById('edit-form').style.display = 'none';
        document.getElementById('form-product').style.display = 'flex';
        loadProducts();
    }

    //Cancell the products edition
    function cancelarEdicao() {
        //Clear the edition fields and hides the form
        document.getElementById('form-edit-product').reset();
        document.getElementById('edit-form').style.display = 'none';
        document.getElementById('form-product').style.display = 'flex';
    }

    //Delete a product
    function excluirProduct(index) {
        if (confirm('Are you sure you want to delete it?')) {
            let products = JSON.parse(localStorage.getItem('products')) || [];
            products.splice(index, 1);
            localStorage.setItem('products', JSON.stringify(products));
            loadProducts();
        }
    }

    //Load the products when load the page
    document.addEventListener('DOMContentLoaded', () => {
        loadProducts();
        document.getElementById('form-product').addEventListener('submit', addProduct);
        document.getElementById('form-edit-product').addEventListener('submit', saveEdit);
    });

    //Delete localStorage
    function deleteLocalStorage() {
    var confirmed = window.confirm('Are you sure you want to delete all Local Storage items?');
    if (confirmed) {
        localStorage.clear();
        alert('Local Storage items have been deleted.');
        location.reload();
    } else {
        //Do nothing if user cancels
        return;
    }
    }
</script>

</body>
</html>
