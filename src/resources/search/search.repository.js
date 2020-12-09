import { Data } from '../tool/data.model';
import { Course } from '../course/course.model';
import _ from 'lodash';
import moment from 'moment';

export function getObjectResult(type, searchAll, searchQuery, startIndex, maxResults, sort) {
    let collection = Data; 
    if (type === 'course') collection = Course;
    var newSearchQuery = JSON.parse(JSON.stringify(searchQuery));
    newSearchQuery["$and"].push({ type: type })

    if (type === 'course') {
        newSearchQuery["$and"].forEach((x) => {
            if (x.$or) {
                x.$or.forEach((y) => {
                    if (y['courseOptions.startDate']) y['courseOptions.startDate'] = new Date (y['courseOptions.startDate'])
                })
            }
        })
        newSearchQuery["$and"].push({$or:[{"courseOptions.startDate": { $gte: new Date(Date.now())}}, { 'courseOptions.flexibleDates':true}]});
    }

    var queryObject;
    if (type === 'course') {
        queryObject = [
            { $unwind: '$courseOptions' },
            { $match: newSearchQuery },
            {
                $project: {
                    "_id": 0,
                    "id": 1,
                    "title": 1,
                    "provider": 1,
                    "type": 1,
                    "description": 1,
                    "courseOptions.flexibleDates": 1,
                    "courseOptions.startDate": 1,
                    "courseOptions.studyMode": 1,
                    "domains": 1,
                    "award": 1
                }
            }
        ];
    }
    else {
        queryObject = [
            { $match: newSearchQuery },
            { $lookup: { from: "tools", localField: "authors", foreignField: "id", as: "persons" } },
            {
                $project: {
                    "_id": 0,
                    "id": 1,
                    "name": 1,
                    "type": 1,
                    "description": 1,
                    "bio": {
                            $cond: {
                            if: { $eq: [ false, "$showBio" ] },
                            then: "$$REMOVE",
                            else: "$bio"
                        }
                    },
                    "categories.category": 1,
                    "categories.programmingLanguage": 1,
                    "programmingLanguage.programmingLanguage": 1,
                    "programmingLanguage.version": 1,
                    "license": 1,
                    "tags.features": 1,
                    "tags.topics": 1,
                    "firstname": 1,
                    "lastname": 1,
                    "datasetid": 1,
                    "pid": 1,
                    "datasetfields.publisher": 1,
                    "datasetfields.geographicCoverage": 1,
                    "datasetfields.physicalSampleAvailability": 1,
                    "datasetfields.abstract": 1,
                    "datasetfields.ageBand": 1,
                    "datasetfields.phenotypes": 1,
                    "datasetv2": 1,

                    "persons.id": 1,
                    "persons.firstname": 1,
                    "persons.lastname": 1,

                    "activeflag": 1,
                    "counter": 1,
                    "datasetfields.metadataquality.quality_score": 1
                }
            }
        ];
    }
    
    if (sort === '' || sort ==='relevance') {
        if (type === "person") {
            if (searchAll) queryObject.push({ "$sort": { "lastname": 1 }});
            else queryObject.push({ "$sort": { score: { $meta: "textScore" }}});
        }
        else {
            if (searchAll) queryObject.push({ "$sort": { "name": 1 }});
            else queryObject.push({ "$sort": { score: { $meta: "textScore" }}});
        }
    }
    else if (sort === 'popularity') {
        if (type === "person") {
            if (searchAll) queryObject.push({ "$sort": { "counter": -1, "lastname": 1 }});
            else queryObject.push({ "$sort": { "counter": -1, score: { $meta: "textScore" }}});
        }
        else {
            if (searchAll) queryObject.push({ "$sort": { "counter": -1, "name": 1 }});
            else queryObject.push({ "$sort": { "counter": -1, score: { $meta: "textScore" }}});
        }
    }
    else if (sort === 'metadata') {
        if (searchAll) queryObject.push({ "$sort": { "datasetfields.metadataquality.quality_score": -1, "name": 1 }});
        else queryObject.push({ "$sort": { "datasetfields.metadataquality.quality_score": -1, score: { $meta: "textScore" }}});
    }
    else if (sort === 'startdate') {
        if (searchAll) queryObject.push({ "$sort": { "courseOptions.startDate": 1 }});
        else queryObject.push({ "$sort": { "courseOptions.startDate": 1, score: { $meta: "textScore" }}});
    }
    
    var q = collection.aggregate(queryObject).skip(parseInt(startIndex)).limit(parseInt(maxResults));
    return new Promise((resolve, reject) => {
        q.exec((err, data) => {
            if (typeof data === "undefined") resolve([]);
            else resolve(data);
        })
    })
}

export function getObjectCount(type, searchAll, searchQuery) {
    let collection = Data;
    if (type === 'course') collection = Course;
    var newSearchQuery = JSON.parse(JSON.stringify(searchQuery));
    newSearchQuery["$and"].push({ type: type })
    if (type === 'course') {
        newSearchQuery["$and"].forEach((x) => {
            if (x.$or) {
                x.$or.forEach((y) => {
                    if (y['courseOptions.startDate']) y['courseOptions.startDate'] = new Date (y['courseOptions.startDate'])
                })
            }
        })
        newSearchQuery["$and"].push({$or:[{"courseOptions.startDate": { $gte: new Date(Date.now())}}, { 'courseOptions.flexibleDates':true}]});
    }
    
    var q = '';
    if (type === 'course') {
        if (searchAll) {
            q = collection.aggregate([
                { $unwind: '$courseOptions' },
                { $match: newSearchQuery }, 
                {
                    "$group": {
                        "_id": {},
                        "count": {
                            "$sum": 1
                        }
                    }
                }, 
                {
                    "$project": {
                        "count": "$count",
                        "_id": 0
                    }
                }
            ]);
        }
        else {
            q = collection.aggregate([
                { $unwind: '$courseOptions' },
                { $match: newSearchQuery }, 
                {
                    "$group": {
                        "_id": {},
                        "count": {
                            "$sum": 1
                        }
                    }
                }, 
                {
                    "$project": {
                        "count": "$count",
                        "_id": 0
                    }
                }
            ]).sort({ score: { $meta: "textScore" } });
        }
    }
    else {
        if (searchAll) {
            q = collection.aggregate([
                { $match: newSearchQuery }, 
                {
                    "$group": {
                        "_id": {},
                        "count": {
                            "$sum": 1
                        }
                    }
                }, 
                {
                    "$project": {
                        "count": "$count",
                        "_id": 0
                    }
                }
            ]);
        }
        else {
            q = collection.aggregate([
                { $match: newSearchQuery },
                {
                    "$group": {
                        "_id": {},
                        "count": {
                            "$sum": 1
                        }
                    }
                }, 
                {
                    "$project": {
                        "count": "$count",
                        "_id": 0
                    }
                }
            ]).sort({ score: { $meta: "textScore" } });
        }
    }
    
    return new Promise((resolve, reject) => {
        q.exec((err, data) => {
            if (typeof data === "undefined") resolve([]);
            else resolve(data);
        })
    })
}

export function getObjectFilters(searchQueryStart, req, type) {
    var searchQuery = JSON.parse(JSON.stringify(searchQueryStart));
    
    let { 
        license = '', sampleavailability = '', keywords = '', publisher = '', ageband = '', geographiccover = '', phenotypes = '', 
        programmingLanguage = '', toolcategories = '', features = '', tooltopics = '', 
        projectcategories = '', projectfeatures = '', projecttopics = '', 
        paperfeatures = '', papertopics = '', 
        coursestartdates = '', coursedomains = '', coursekeywords = '', courseprovider = '', courselocation = '', coursestudymode = '', courseaward = '', courseentrylevel = '', courseframework = '', coursepriority = ''
    } = req.query;

    if (type === "dataset") {
        if (license.length > 0) {
            var filterTermArray = [];
            license.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "license": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }

        if (sampleavailability.length > 0) {
            var filterTermArray = [];
            sampleavailability.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "datasetfields.physicalSampleAvailability": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }

        if (keywords.length > 0) {
            var filterTermArray = [];
            keywords.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "tags.features": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }

        if (publisher.length > 0) {
            var filterTermArray = [];
            publisher.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "datasetfields.publisher": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }

        if (ageband.length > 0) {
            var filterTermArray = [];
            ageband.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "datasetfields.ageBand": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }

        if (geographiccover.length > 0) {
            var filterTermArray = [];
            geographiccover.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "datasetfields.geographicCoverage": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }

        if (phenotypes.length > 0) {
            var filterTermArray = [];
            phenotypes.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "datasetfields.phenotypes.name": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }
    }

    if (type === "tool") {
        if (programmingLanguage.length > 0) {
            var filterTermArray = [];
            programmingLanguage.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "programmingLanguage.programmingLanguage": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }

        if (toolcategories.length > 0) {
            var filterTermArray = [];
            toolcategories.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "categories.category": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }

        if (features.length > 0) {
            var filterTermArray = [];
            features.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "tags.features": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }

        if (tooltopics.length > 0) {
            var filterTermArray = [];
            tooltopics.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "tags.topics": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }
    }
    else if (type === "project") {
        if (projectcategories.length > 0) {
            var filterTermArray = [];
            projectcategories.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "categories.category": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }

        if (projectfeatures.length > 0) {
            var filterTermArray = [];
            projectfeatures.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "tags.features": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }

        if (projecttopics.length > 0) {
            var filterTermArray = [];
            projecttopics.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "tags.topics": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }
    }
    else if (type === "paper") {
        if (paperfeatures.length > 0) {
            var filterTermArray = [];
            paperfeatures.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "tags.features": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }

        if (papertopics.length > 0) {
            var filterTermArray = [];
            papertopics.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "tags.topics": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }
    }
    else if (type === "course") {
        if (coursestartdates.length > 0) {
            var filterTermArray = [];
            coursestartdates.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "courseOptions.startDate": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }

        if (courseprovider.length > 0) {
            var filterTermArray = [];
            courseprovider.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "provider": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }

        if (courselocation.length > 0) {
            var filterTermArray = [];
            courselocation.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "location": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }

        if (coursestudymode.length > 0) {
            var filterTermArray = [];
            coursestudymode.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "courseOptions.studyMode": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }

        if (courseaward.length > 0) {
            var filterTermArray = [];
            courseaward.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "award": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }

        if (courseentrylevel.length > 0) {
            var filterTermArray = [];
            courseentrylevel.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "entries.level": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }

        if (coursedomains.length > 0) {
            var filterTermArray = [];
            coursedomains.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "domains": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }

        if (coursekeywords.length > 0) {
            var filterTermArray = [];
            coursekeywords.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "keywords": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }
        
        if (courseframework.length > 0) {
            var filterTermArray = [];
            courseframework.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "competencyFramework": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }

        if (coursepriority.length > 0) {
            var filterTermArray = [];
            coursepriority.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "nationalPriority": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }
    }
    return searchQuery;
}

export const getFilter = async (searchString, type, field, isArray, activeFiltersQuery) => {
    return new Promise(async (resolve, reject) => {
        let collection = Data;
        if (type === 'course') collection = Course;
        var q = '', p = '';
        var combinedResults = [], activeCombinedResults = [];

        if (searchString) q = collection.aggregate(filterQueryGenerator(field, searchString, type, isArray, {}));
        else q = collection.aggregate(filterQueryGenerator(field, '', type, isArray, {}));
        
        q.exec((err, data) => {
            if (err) return resolve({})

            if (data.length) {
                data.forEach((dat) => {
                    if (dat.result && dat.result !== '') {
                        if (field === 'datasetfields.phenotypes') combinedResults.push(dat.result.name.trim());
                        else if (field === 'courseOptions.startDate') combinedResults.push(moment(dat.result).format("DD MMM YYYY"));
                        else combinedResults.push(dat.result.trim());
                    }
                })
            }
 
            var newSearchQuery = JSON.parse(JSON.stringify(activeFiltersQuery));
            newSearchQuery["$and"].push({ type: type })
            
            if (searchString) p = collection.aggregate(filterQueryGenerator(field, searchString, type, isArray, newSearchQuery));
            else p = collection.aggregate(filterQueryGenerator(field, '', type, isArray, newSearchQuery));
            
            p.exec((activeErr, activeData) => {
                if (activeData.length) {
                    activeData.forEach((dat) => {
                        if (dat.result && dat.result !== '') {
                            if (field === 'datasetfields.phenotypes') activeCombinedResults.push(dat.result.name.trim());
                            else if (field === 'courseOptions.startDate') activeCombinedResults.push(moment(dat.result).format("DD MMM YYYY"));
                            else activeCombinedResults.push(dat.result.trim());
                        }
                    })
                }
                resolve([combinedResults, activeCombinedResults]);
            });
        });
    })
}

export function filterQueryGenerator(filter, searchString, type, isArray, activeFiltersQuery) {
    var queryArray = []

    if (type === "course") {
        queryArray.push({ $unwind: '$courseOptions' });
        queryArray.push({ $match: {$or:[{"courseOptions.startDate": { $gte: new Date(Date.now())}}, { 'courseOptions.flexibleDates':true}]}});
    }

    if (!_.isEmpty(activeFiltersQuery)) {
        queryArray.push({ $match: activeFiltersQuery});
    }
    else {
        if (searchString !=='') queryArray.push({ $match: { $and: [{ $text: { $search: searchString } }, { type: type }, { activeflag: 'active' }] } });
        else queryArray.push({ $match: { $and: [{ type: type }, { activeflag: 'active' }] } });
    }

    queryArray.push(
        { 
            "$project" : { 
                "result" : "$"+filter, 
                "_id": 0
            }
        }
    );
    
    if (isArray) {
        queryArray.push({"$unwind": '$result'});
        queryArray.push({"$unwind": '$result'});
    } 

    queryArray.push(
        { 
            "$group" : { 
                "_id" : null, 
                "distinct" : { 
                    "$addToSet" : "$$ROOT"
                }
            }
        }, 
        { 
            "$unwind" : { 
                "path" : "$distinct", 
                "preserveNullAndEmptyArrays" : false
            }
        }, 
        { 
            "$replaceRoot" : { 
                "newRoot" : "$distinct"
            }
        },
        {
            "$sort": {
                "result": 1
            }
        }
    );

    return queryArray;
}