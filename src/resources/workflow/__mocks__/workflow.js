import mongoose from 'mongoose';

module.exports = {
	steps: [
        {
            name: 'Step 1',
            active: true,
            completed: false,
            reviewers: [ 
                new mongoose.Types.ObjectId(),
                new mongoose.Types.ObjectId()    
            ],
            sections: [
                'safepeople'
            ]
        },
        {
            name: 'Step 2',
            active: false,
            completed: false
        },
        {
            name: 'Step 3',
            active: true,
            completed: false
        }
    ]
};
