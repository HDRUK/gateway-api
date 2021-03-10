import { v4 as uuidv4 } from 'uuid';

export const findNodeInTree = (tree, key) => {
  // 1. find if key matches //datasetFeatures
  let found = tree.find(node => node.alias === key || node.key === key);
    // 2. if not found do while
		if (!found) {
			let i = 0;
      // 3. make sure current tree loop has a length
			while(!found && i < tree.length) {
        // 4. check current iteration has filters to avoid expense recursive call
				if (tree[i].filters && tree[i].filters.length) {
          // 5. assign recursive call to found
					found = findNodeInTree(tree[i].filters, key);
				}
        // 6. increment count of i
				i++;
			}
		}
    // 7. return node || recursive call
		return found;
};

export const updateTree = (tree, key, values) => {
  // 1. declare iter 
  let iter = () => {};
  // 2. loop tree with callback
  tree.forEach(iter = (node) => {
    // 3. if found update filters
    if (node.key === key) {
        // 4. test phenotypes **** THIS NEEDS WORK AND FURTHER DEV ****
        // if(key === 'phenotypes') {
        //   values = [...values].map(item => {
        //     return {
        //       id: uuidv4(),
        //       label: item.label.name,
        //       value: item.label.name,
        //       checked: false
        //     } 
        //   }).sort((a, b) => a.label.localeCompare(b.label))
        //   values = uniqBy(values, 'label');
        // }
        // 5. set filter values
        node.filters = values;
    }
    // 6. if has filters recall iter with new filters
    Array.isArray(node.filters) && node.filters.forEach(iter);
  });

  return tree;
}

export const formatFilterOptions = (filters) => {
  // 1. map over the filters and build new options to return
  return [...filters].map((value) => {
    return {
      id: uuidv4(),
      label: value,
      value: value,
      checked: false
    }
  });
}
