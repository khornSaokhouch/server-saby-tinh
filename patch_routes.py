filepath = r'c:\xampp\htdocs\Project-pp\backend\routes\api.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# The corrupted ending to replace
needle = "    Route::delete('/cart', [ShoppingCartController::class, 'clearCart']);"
idx = content.rfind(needle)

if idx == -1:
    print("ERROR: needle not found")
else:
    # Replace everything from that point with clean content
    clean_ending = (
        "    Route::delete('/cart', [ShoppingCartController::class, 'clearCart']);\r\n"
        "\r\n"
        "    // User Addresses (authenticated users)\r\n"
        "    Route::get('/user-addresses', [UserAddressController::class, 'index']);\r\n"
        "    Route::post('/user-addresses', [UserAddressController::class, 'store']);\r\n"
        "    Route::patch('/user-addresses/{id}', [UserAddressController::class, 'update']);\r\n"
        "    Route::delete('/user-addresses/{id}', [UserAddressController::class, 'destroy']);\r\n"
        "});\r\n"
    )
    content = content[:idx] + clean_ending

    with open(filepath, 'w', encoding='utf-8', newline='') as f:
        f.write(content)
    print("Done - routes added successfully")
