<?php
// Start the session to store form data across steps
session_start();

// Define the total number of steps
$total_steps = 4;

// Determine the current step. Default to 1 if not set.
$current_step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($current_step < 1 || $current_step > $total_steps) {
    $current_step = 1;
}

// Initialize session data if it doesn't exist
if (!isset($_SESSION['form_data'])) {
    $_SESSION['form_data'] = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and save data for the submitted step
    $submitted_step = isset($_POST['step']) ? (int)$_POST['step'] : $current_step;
    foreach ($_POST as $key => $value) {
        if ($key !== 'step') {
            $_SESSION['form_data'][$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
    }
    
    // Redirect to the next step
    $next_step = $submitted_step + 1;
    if ($next_step <= $total_steps) {
        header("Location: ?step=" . $next_step);
        exit();
    } else {
        // Optional: Handle final submission (e.g., save to database)
        // For this example, we'll just show a success message or redirect to a final page.
        // To restart the form, we can unset the session data.
        // unset($_SESSION['form_data']);
        // header("Location: ?step=1&success=1");
        // For now, let's just stay on the last step to show it's the end.
        $current_step = $total_steps; 
    }
}

// Function to get a value from session data, to pre-fill fields
function get_data($field) {
    return $_SESSION['form_data'][$field] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Order</title>
    <style>
        /* General Styling */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap');

        :root {
            --primary-color: #ef4444; /* Red theme */
            --border-color: #E0E0E0;
            --text-color: #333;
            --label-color: #555;
            --bg-color: #F4F7FA;
            --card-bg-color: #FFFFFF;
            --active-step-color: #ef4444;
            --inactive-step-color: #D1D5DB;
        }

        body { font-family: 'Inter', sans-serif; background-color: var(--bg-color); color: var(--text-color); margin:0; }
        /* Modal overlay + container to mimic dialog */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.35); display:flex; align-items:center; justify-content:center; padding:20px; }
        .modal-container { background: var(--card-bg-color); border-radius:16px; box-shadow:0 20px 60px rgba(0,0,0,.3); width:100%; max-width:760px; max-height:90vh; overflow:auto; }

        /* Card Layout */
        .order-card { padding: 2rem; box-sizing: border-box; }

        /* Header */
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .card-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .close-btn {
            font-size: 1.5rem;
            color: #999;
            cursor: pointer;
            border: none;
            background: none;
        }

        /* Progress Bar */
        .progress-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2.5rem;
        }
        .progress-step {
            display: flex;
            align-items: center;
            flex-grow: 1;
        }
        .progress-step:last-child {
            flex-grow: 0;
        }
        .step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--card-bg-color);
            border: 2px solid var(--inactive-step-color);
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 500;
            color: var(--inactive-step-color);
            position: relative;
            z-index: 2;
        }
        .step-label {
            position: absolute;
            top: 40px;
            white-space: nowrap;
            color: var(--inactive-step-color);
            font-weight: 500;
            font-size: 0.8rem;
        }
        .step-line {
            height: 2px;
            background-color: var(--inactive-step-color);
            flex-grow: 1;
        }
        /* Active & Completed States */
        .progress-step.active .step-circle,
        .progress-step.completed .step-circle {
            background-color: var(--active-step-color);
            border-color: var(--active-step-color);
            color: white;
        }
         .progress-step.active .step-label {
            color: var(--active-step-color);
         }
        .progress-step.completed .step-line {
            background-color: var(--active-step-color);
        }

        /* Form Styling */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--label-color);
            margin-bottom: 0.5rem;
        }
        input, select, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(0, 135, 90, 0.2);
        }
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        /* Special multi-input groups */
        .multi-input-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .multi-input-group input {
            text-align: center;
        }
        .multi-input-group select {
            flex-shrink: 0;
            width: auto;
        }
        .calculate-btn {
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            background-color: var(--card-bg-color);
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Footer / Navigation */
        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2.5rem;
        }
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        .btn-primary:hover { background-color: #b91c1c; }
        .btn-secondary {
            background-color: var(--card-bg-color);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        /* Responsive */
        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <div class="modal-overlay">
      <div class="modal-container">
        <div class="order-card">
        <header class="card-header">
            <h1>Create order</h1>
            <button class="close-btn" aria-label="Close">&times;</button>
        </header>

        <div class="progress-bar">
            <?php 
            $step_labels = ['Basic Information', 'Location Details', 'Property Details', 'Images & Review'];
            for ($i = 1; $i <= $total_steps; $i++): 
                $step_class = '';
                if ($i == $current_step) $step_class = 'active';
                if ($i < $current_step) $step_class = 'completed';
            ?>
            <div class="progress-step <?php echo $step_class; ?>">
                <div class="step-circle">
                    <?php if ($i < $current_step): ?>
                        &#10003; <?php else: ?>
                        <?php echo $i; ?>
                    <?php endif; ?>
                    <span class="step-label"><?php echo $step_labels[$i-1]; ?></span>
                </div>
                <?php if ($i < $total_steps): ?>
                    <div class="step-line"></div>
                <?php endif; ?>
            </div>
            <?php endfor; ?>
        </div>
        
        <form method="POST">
            <input type="hidden" name="step" value="<?php echo $current_step; ?>">

            <?php if ($current_step == 1): ?>
            <div class="form-grid">
                <div class="form-group full-width">
                    <label for="title">Property Title</label>
                    <input type="text" id="title" name="title" value="<?php echo get_data('title'); ?>" placeholder="Beautiful 3BHK Apartment in Downtown">
                </div>
                <div class="form-group">
                    <label for="listing_type">Listing Type</label>
                    <select id="listing_type" name="listing_type">
                        <option value="Buy" <?php echo get_data('listing_type') == 'Buy' ? 'selected' : ''; ?>>Buy</option>
                        <option value="Rent" <?php echo get_data('listing_type') == 'Rent' ? 'selected' : ''; ?>>Rent</option>
                        <option value="PG/Co-living" <?php echo get_data('listing_type') == 'PG/Co-living' ? 'selected' : ''; ?>>PG/Co-living</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="Available" <?php echo get_data('status') == 'Available' ? 'selected' : ''; ?>>Available</option>
                        <option value="Sold" <?php echo get_data('status') == 'Sold' ? 'selected' : ''; ?>>Sold</option>
                        <option value="Rented" <?php echo get_data('status') == 'Rented' ? 'selected' : ''; ?>>Rented</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="price">Price (₹)</label>
                    <input type="number" step="0.01" id="price" name="price" value="<?php echo get_data('price'); ?>" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label for="area">Area (sq ft)</label>
                    <input type="number" step="0.01" id="area" name="area" value="<?php echo get_data('area'); ?>" placeholder="0">
                </div>
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" value="<?php echo get_data('location'); ?>" placeholder="City, Area, Locality">
                </div>
                <div class="form-group">
                    <label for="landmark">Landmark</label>
                    <input type="text" id="landmark" name="landmark" value="<?php echo get_data('landmark'); ?>" placeholder="Nearby landmark or reference point">
                </div>
                <div class="form-group">
                    <label for="configuration">Configuration</label>
                    <input type="text" id="configuration" name="configuration" value="<?php echo get_data('configuration'); ?>" placeholder="e.g., 2BHK, 3BHK">
                </div>
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">Select Category</option>
                        <option value="1" <?php echo get_data('category_id') == '1' ? 'selected' : ''; ?>>Residential</option>
                        <option value="2" <?php echo get_data('category_id') == '2' ? 'selected' : ''; ?>>Commercial</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Describe the property features, amenities, and unique selling points..."><?php echo get_data('description'); ?></textarea>
                </div>
                <div class="form-group full-width">
                    <label for="map_embed_link">Map Embed Link</label>
                    <input type="url" id="map_embed_link" name="map_embed_link" value="<?php echo get_data('map_embed_link'); ?>" placeholder="https://www.google.com/maps/embed?pb=...">
                </div>
            </div>
            <?php elseif ($current_step == 2): ?>
            <div class="form-grid">
                <div class="form-group">
                    <label for="state_id">State</label>
                    <select id="state_id" name="state_id">
                        <option value="">Select State</option>
                        <option value="1" <?php echo get_data('state_id') == '1' ? 'selected' : ''; ?>>State A</option>
                        <option value="2" <?php echo get_data('state_id') == '2' ? 'selected' : ''; ?>>State B</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="district_id">District</label>
                    <select id="district_id" name="district_id">
                        <option value="">Select District</option>
                        <option value="10" <?php echo get_data('district_id') == '10' ? 'selected' : ''; ?>>District X</option>
                        <option value="11" <?php echo get_data('district_id') == '11' ? 'selected' : ''; ?>>District Y</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="city_id">City</label>
                    <select id="city_id" name="city_id">
                        <option value="">Select City</option>
                        <option value="100" <?php echo get_data('city_id') == '100' ? 'selected' : ''; ?>>City M</option>
                        <option value="101" <?php echo get_data('city_id') == '101' ? 'selected' : ''; ?>>City N</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="town_id">Town</label>
                    <select id="town_id" name="town_id">
                        <option value="">Select Town</option>
                        <option value="200" <?php echo get_data('town_id') == '200' ? 'selected' : ''; ?>>Town 1</option>
                        <option value="201" <?php echo get_data('town_id') == '201' ? 'selected' : ''; ?>>Town 2</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="pincode">Pincode</label>
                    <input type="text" id="pincode" name="pincode" value="<?php echo get_data('pincode'); ?>" placeholder="560001">
                </div>
            </div>
            <?php elseif ($current_step == 3): ?>
            <div class="form-grid">
                <div class="form-group">
                    <label for="furniture_status">Furniture Status</label>
                    <select id="furniture_status" name="furniture_status">
                        <option value="">Select</option>
                        <option value="Furnished" <?php echo get_data('furniture_status') == 'Furnished' ? 'selected' : ''; ?>>Furnished</option>
                        <option value="Semi-Furnished" <?php echo get_data('furniture_status') == 'Semi-Furnished' ? 'selected' : ''; ?>>Semi-Furnished</option>
                        <option value="Unfurnished" <?php echo get_data('furniture_status') == 'Unfurnished' ? 'selected' : ''; ?>>Unfurnished</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="ownership_type">Ownership Type</label>
                    <select id="ownership_type" name="ownership_type">
                        <option value="">Select</option>
                        <option value="Freehold" <?php echo get_data('ownership_type') == 'Freehold' ? 'selected' : ''; ?>>Freehold</option>
                        <option value="Leasehold" <?php echo get_data('ownership_type') == 'Leasehold' ? 'selected' : ''; ?>>Leasehold</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="facing">Facing</label>
                    <select id="facing" name="facing">
                        <option value="">Select</option>
                        <option value="East" <?php echo get_data('facing') == 'East' ? 'selected' : ''; ?>>East</option>
                        <option value="West" <?php echo get_data('facing') == 'West' ? 'selected' : ''; ?>>West</option>
                        <option value="North" <?php echo get_data('facing') == 'North' ? 'selected' : ''; ?>>North</option>
                        <option value="South" <?php echo get_data('facing') == 'South' ? 'selected' : ''; ?>>South</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="parking">Parking</label>
                    <select id="parking" name="parking">
                        <option value="">Select</option>
                        <option value="Yes" <?php echo get_data('parking') == 'Yes' ? 'selected' : ''; ?>>Yes</option>
                        <option value="No" <?php echo get_data('parking') == 'No' ? 'selected' : ''; ?>>No</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="balcony">Number of Balconies</label>
                    <input type="number" id="balcony" name="balcony" value="<?php echo get_data('balcony'); ?>" min="0" placeholder="0">
                </div>
            </div>
            <?php elseif ($current_step == 4): ?>
            <div>
                <div class="form-group full-width">
                    <label for="images">Property Images</label>
                    <input type="file" id="images" name="images[]" multiple>
                    <small class="step-hint">Upload multiple images (JPG, PNG, GIF, WebP) - Max 5MB each</small>
                </div>
                <div style="margin-top:1rem; color:#6b7280; font-size:0.9rem;">
                    Review your property details and add images before submitting.
                </div>
            </div>
            <?php endif; ?>
            
            <footer class="card-footer">
                <?php if ($current_step > 1): ?>
                    <a href="?step=<?php echo $current_step - 1; ?>" class="btn btn-secondary">&leftarrow; Back</a>
                <?php else: ?>
                    <div></div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary">
                    <?php echo ($current_step == $total_steps) ? 'Finish' : 'Continue &rightarrow;'; ?>
                </button>
            </footer>
        </form>
        </div>
      </div>
    </div>

</body>
</html>