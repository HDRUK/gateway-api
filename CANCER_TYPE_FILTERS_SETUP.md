# Cancer Type Filters API Setup

## Overview
This API provides endpoints to manage and retrieve hierarchical cancer type filter data.

## Setup Instructions

### 1. Convert JavaScript to JSON

The data you provided is in JavaScript format:
```javascript
const theFilters = {...}
```

To convert it to JSON:

**Option A: Using Node.js**
```bash
# Create a file filters.js with your data (remove "const theFilters = " and ";")
node -e "const fs = require('fs'); const data = require('./filters.js'); fs.writeFileSync('storage/app/cancer_type_filters.json', JSON.stringify(data, null, 2));"
```

**Option B: Using Online Converter**
1. Copy your JavaScript object
2. Remove `const theFilters = ` and the trailing `;`
3. Use an online JavaScript to JSON converter (e.g., https://jsonformatter.org/js-to-json)
4. Save the result as `storage/app/cancer_type_filters.json`

**Option C: Direct Paste**
1. Copy your JavaScript object
2. Remove `const theFilters = ` and the trailing `;`
3. Wrap it in `JSON.parse()` or use a JSON validator
4. Save as `storage/app/cancer_type_filters.json`

### 2. Run Migration

```bash
php artisan migrate
```

### 3. Seed the Data

```bash
php artisan db:seed --class=CancerTypeFilterSeeder
```

## API Endpoints

### Get All Filters (Hierarchical)
```
GET /api/v1/cancer-type-filters
```

Query Parameters:
- `parent_id` (optional): Filter by parent ID
- `level` (optional): Filter by hierarchy level

### Get Single Filter
```
GET /api/v1/cancer-type-filters/{id}
```

## Database Structure

The `cancer_type_filters` table has the following structure:
- `id`: Primary key
- `filter_id`: Unique identifier (e.g., "0_0", "0_0_0")
- `label`: Display label
- `category`: Category name
- `primary_group`: Primary group name
- `count`: Count value
- `parent_id`: Foreign key to parent filter (nullable)
- `level`: Hierarchy depth level
- `sort_order`: Sort order within level

## Example Response

```json
{
  "data": [
    {
      "id": 1,
      "filter_id": "0_0",
      "label": "cancerTypes",
      "category": "filters",
      "primary_group": "cancer-type",
      "count": "0",
      "parent_id": null,
      "level": 0,
      "children": [
        {
          "id": 2,
          "filter_id": "0_0_0",
          "label": "icdOTopography",
          "category": "cancerTypes",
          "primary_group": "cancer-type",
          "count": "0",
          "parent_id": 1,
          "level": 1,
          "children": [...]
        }
      ]
    }
  ]
}
```

