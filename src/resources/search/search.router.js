import express from 'express'
import axios from 'axios';

import { RecordSearchData } from '../search/record.search.model';
import { Data } from '../tool/data.model'

const router = express.Router();
  
/**
 * {get} /api/search Search tools
 * 
 * Return list of tools, this can be with filters or/and search criteria. This will also include pagination on results.
 * The free word search criteria can be improved on with node modules that specialize with searching i.e. js-search
 */
router.get('/', async (req, res) => {
    var authorID = parseInt(req.query.userID);
    var searchString = req.query.search || ""; //If blank then return all
    let searchQuery = { $and: [{ activeflag: 'active' }] };

    if(req.query.form){
        searchQuery = {$and:[{$or:[{$and:[{activeflag:'review'},{authors:authorID}]},{activeflag:'active'}]}]};
    }

    var searchAll = false;

    if (searchString.length > 0) {
        searchQuery["$and"].push({ $text: { $search: searchString } });

        /* datasetSearchString = '"' + searchString.split(' ').join('""') + '"';
        //The following code is a workaround for the way search works TODO:work with MDC to improve API
        if (searchString.match(/"/)) {
            //user has added quotes so pass string through
            datasetSearchString = searchString;
        } else {
            //no quotes so lets a proximiy search
            datasetSearchString = '"'+searchString+'"~25';
        } */
    }
    else {
        searchAll = true;
    }
    
    await Promise.all([
        getObjectResult('dataset', searchAll, getObjectFilters(searchQuery, req, 'dataset')),
        getObjectResult('tool', searchAll, getObjectFilters(searchQuery, req, 'tool')),
        getObjectResult('project', searchAll, getObjectFilters(searchQuery, req, 'project')),
        getObjectResult('paper', searchAll, getObjectFilters(searchQuery, req, 'paper')),
        getObjectResult('person', searchAll, searchQuery),
    ]).then((values) => {
        var datasetCount = values[0].length || 0;
        var toolCount = values[1].length || 0;
        var projectCount = values[2].length || 0;
        var paperCount = values[3].length || 0;
        var personCount = values[4].length || 0;

        let recordSearchData = new RecordSearchData();
        recordSearchData.searched = searchString;
        recordSearchData.returned.dataset = datasetCount;
        recordSearchData.returned.tool = toolCount;
        recordSearchData.returned.project = projectCount;
        recordSearchData.returned.paper = paperCount;
        recordSearchData.returned.person = personCount;
        recordSearchData.datesearched = Date.now();
        recordSearchData.save((err) => { });

        var filterOptions = getFilterOptions(values)
        var summary = { datasets: datasetCount, tools: toolCount, projects: projectCount, papers: paperCount, persons: personCount }
        
        var datasetIndex = req.query.datasetIndex || 0;
        var toolIndex = req.query.toolIndex || 0;
        var projectIndex = req.query.projectIndex || 0;
        var paperIndex = req.query.paperIndex || 0;
        var personIndex = req.query.personIndex || 0;
        var maxResults = req.query.maxResults || 40;

        var datasetList = values[0].slice(datasetIndex, (+datasetIndex + +maxResults));
        var toolList = values[1].slice(toolIndex, (+toolIndex + +maxResults));
        var projectList = values[2].slice(projectIndex, (+projectIndex + +maxResults));
        var paperList = values[3].slice(paperIndex, (+paperIndex + +maxResults));
        var personList = values[4].slice(personIndex, (+personIndex + +maxResults));
        
        return res.json({
            success: true,
            datasetResults: datasetList,
            toolResults: toolList,
            projectResults: projectList,
            paperResults: paperList,
            personResults: personList,
            filterOptions: filterOptions,
            summary: summary
        });
    });
});

function getObjectResult(type, searchAll, searchQuery) {
    var newSearchQuery = JSON.parse(JSON.stringify(searchQuery));
    newSearchQuery["$and"].push({ type: type })

    var q = '';
    
    if (searchAll) {
        q = Data.aggregate([
            { $match: newSearchQuery }, 
            { $lookup: { from: "tools", localField: "authors", foreignField: "id", as: "persons" } },            
            {
                $project: {
                            "_id": 0, 
                            "id": 1,
                            "name": 1,
                            "type": 1,
                            "description": 1,
                            "bio": 1,
                            "categories.category": 1,
                            "categories.programmingLanguage": 1,
                            "license": 1,
                            "tags.features": 1,
                            "tags.topics": 1,   
                            "firstname": 1,
                            "lastname": 1,
                            "datasetid": 1,

                            "datasetfields.publisher": 1,
                            "datasetfields.geographicCoverage": 1,
                            "datasetfields.physicalSampleAvailability": 1,
                            "datasetfields.abstract": 1,
                            "datasetfields.ageBand": 1,

                            "persons.id": 1,
                            "persons.firstname": 1,
                            "persons.lastname": 1,

                            "activeflag": 1,
                          }
              }
        ]).sort({ name : 1 });
    }
    else {
        q = Data.aggregate([
            { $match: newSearchQuery },
            { $lookup: { from: "tools", localField: "authors", foreignField: "id", as: "persons" } },
            {
                $project: {
                            "_id": 0, 
                            "id": 1,
                            "name": 1,
                            "type": 1,
                            "description": 1,
                            "bio": 1,
                            "categories.category": 1,
                            "categories.programmingLanguage": 1,
                            "license": 1,
                            "tags.features": 1,
                            "tags.topics": 1,   
                            "firstname": 1,
                            "lastname": 1,
                            "datasetid": 1,

                            "datasetfields.publisher": 1,
                            "datasetfields.geographicCoverage": 1,
                            "datasetfields.physicalSampleAvailability": 1,
                            "datasetfields.abstract": 1,
                            "datasetfields.ageBand": 1,

                            "persons.id": 1,
                            "persons.firstname": 1,
                            "persons.lastname": 1,

                            "activeflag": 1,

                          }
              }
        ]).sort({ score: { $meta: "textScore" } });
    }
    
    return new Promise((resolve, reject) => {
        q.exec((err, data) => {
            if (typeof data === "undefined") resolve([]);
            else resolve(data);
        })
    })
}


function getObjectFilters(searchQueryStart, req, type) {
    var searchQuery = JSON.parse(JSON.stringify(searchQueryStart));
    
    var license = req.query.license || "";
    var sample = req.query.sampleavailability || "";
    var datasetfeature = req.query.keywords || "";
    var publisher = req.query.publisher || "";
    var ageBand = req.query.ageband || "";
    var geographicCoverage = req.query.geographiccover || "";

    var programmingLanguage = req.query.programmingLanguage || "";
    var toolcategories = req.query.toolcategories || "";
    var features = req.query.features || "";
    var tooltopics = req.query.tooltopics || "";

    var projectcategories = req.query.projectcategories || "";
    var projectfeatures = req.query.projectfeatures || "";
    var projecttopics = req.query.projecttopics || "";

    var paperfeatures = req.query.paperfeatures || "";
    var papertopics = req.query.papertopics || "";

    if (type === "dataset") {
        if (license.length > 0) {
            var filterTermArray = [];
            license.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "license": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }

        if (sample.length > 0) {
            var filterTermArray = [];
            sample.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "datasetfields.physicalSampleAvailability": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }

        if (datasetfeature.length > 0) {
            var filterTermArray = [];
            datasetfeature.split('::').forEach((filterTerm) => {
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

        if (ageBand.length > 0) {
            var filterTermArray = [];
            ageBand.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "datasetfields.ageBand": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }

        if (geographicCoverage.length > 0) {
            var filterTermArray = [];
            geographicCoverage.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "datasetfields.geographicCoverage": filterTerm })
            });
            searchQuery["$and"].push({ "$or": filterTermArray });
        }
    }

    if (type === "tool") {
        if (programmingLanguage.length > 0) {
            var filterTermArray = [];
            programmingLanguage.split('::').forEach((filterTerm) => {
                filterTermArray.push({ "categories.programmingLanguage": filterTerm })
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
    return searchQuery;
}

/* function getDatasetFilters(req) {
    var filterString = '';
    if (req.query.publisher) {
        if (typeof (req.query.publisher) == 'string') {
            req.query.publisher.split('::').forEach((filterTerm) => {
                filterString += '&publisher=' + filterTerm;
            });
        }
    }
    if (req.query.license) {
        if (typeof (req.query.license) == 'string') {
            req.query.license.split('::').forEach((filterTerm) => {
                filterString += '&license=' + filterTerm;
            });
        }
    }
    if (req.query.geographiccover) {
        if (typeof (req.query.geographiccover) == 'string') {
            req.query.geographiccover.split('::').forEach((filterTerm) => {
                filterString += '&geographicCoverage=' + filterTerm;
            });
        }
    }
    if (req.query.ageband) {
        if (typeof (req.query.ageband) == 'string') {
            req.query.ageband.split('::').forEach((filterTerm) => {
                filterString += '&ageBand=' + filterTerm.replace("+", "%2B");;
            });
        }
    }
    if (req.query.sampleavailability) {
        if (typeof (req.query.sampleavailability) == 'string') {
            req.query.sampleavailability.split('::').forEach((filterTerm) => {
                filterString += '&physicalSampleAvailability=' + filterTerm;
            });
        }
    }
    if (req.query.keywords) {
        if (typeof (req.query.keywords) == 'string') {
            req.query.keywords.split('::').forEach((filterTerm) => {
                filterString += '&keywords=' + filterTerm;
            });
        }
    }
    return filterString;
} */

function getFilterOptions(values) {
    var licenseFilterOptions = [];
    var sampleFilterOptions = [];
    var datasetFeaturesFilterOptions = [];
    var publisherFilterOptions = [];
    var ageBandFilterOptions = [];
    var geographicCoverageFilterOptions = [];

    var toolCategoriesFilterOptions = [];
    var programmingLanguageFilterOptions = [];
    var featuresFilterOptions = [];
    var toolTopicsFilterOptions = [];

    var projectCategoriesFilterOptions = [];
    var projectFeaturesFilterOptions = [];
    var projectTopicsFilterOptions = [];

    var paperFeaturesFilterOptions = [];
    var paperTopicsFilterOptions = [];

    values[0].forEach((dataset) => {
        if (dataset.license && dataset.license !== '' && !licenseFilterOptions.includes(dataset.license)) {
            licenseFilterOptions.push(dataset.license);
        }

        if (dataset.datasetfields.physicalSampleAvailability && dataset.datasetfields.physicalSampleAvailability.length > 0) {
            dataset.datasetfields.physicalSampleAvailability.forEach((fe) => {
                if (!sampleFilterOptions.includes(fe) && fe !== '') {
                    sampleFilterOptions.push(fe);
                }
            });
        }

        if (dataset.tags.features && dataset.tags.features.length > 0) {
            dataset.tags.features.forEach((fe) => {
                if (!datasetFeaturesFilterOptions.includes(fe) && fe !== '') {
                    datasetFeaturesFilterOptions.push(fe);
                }
            });
        }
       
        if (dataset.datasetfields.publisher && dataset.datasetfields.publisher !== '' && !publisherFilterOptions.includes(dataset.datasetfields.publisher)) {
            publisherFilterOptions.push(dataset.datasetfields.publisher);
        }

        if (dataset.datasetfields.ageBand && dataset.datasetfields.ageBand !== '' && !ageBandFilterOptions.includes(dataset.datasetfields.ageBand)) {
            ageBandFilterOptions.push(dataset.datasetfields.ageBand);
        }

        if (dataset.datasetfields.geographicCoverage && dataset.datasetfields.geographicCoverage !== '' && !geographicCoverageFilterOptions.includes(dataset.datasetfields.geographicCoverage)) {
            geographicCoverageFilterOptions.push(dataset.datasetfields.geographicCoverage);
        }
    })

    values[1].forEach((tool) => {
        if (tool.categories && tool.categories.category && tool.categories.category !== '' && !toolCategoriesFilterOptions.includes(tool.categories.category)) {
            toolCategoriesFilterOptions.push(tool.categories.category);
        }

        if (tool.categories.programmingLanguage && tool.categories.programmingLanguage.length > 0) {
            tool.categories.programmingLanguage.forEach((pl) => {
                if (!programmingLanguageFilterOptions.includes(pl) && pl !== '') {
                    programmingLanguageFilterOptions.push(pl);
                }
            });
        }

        if (tool.tags.features && tool.tags.features.length > 0) {
            tool.tags.features.forEach((fe) => {
                if (!featuresFilterOptions.includes(fe) && fe !== '') {
                    featuresFilterOptions.push(fe);
                }
            });
        }

        if (tool.tags.topics && tool.tags.topics.length > 0) {
            tool.tags.topics.forEach((to) => {
                if (!toolTopicsFilterOptions.includes(to) && to !== '') {
                    toolTopicsFilterOptions.push(to);
                }
            });
        }
    })

    values[2].forEach((project) => {
        if (project.categories && project.categories.category && project.categories.category !== '' && !projectCategoriesFilterOptions.includes(project.categories.category)) {
            projectCategoriesFilterOptions.push(project.categories.category);
        }

        if (project.tags.features && project.tags.features.length > 0) {
            project.tags.features.forEach((pf) => {
                if (!projectFeaturesFilterOptions.includes(pf) && pf !== '') {
                    projectFeaturesFilterOptions.push(pf);
                }
            });
        }

        if (project.tags.topics && project.tags.topics.length > 0) {
            project.tags.topics.forEach((pto) => {
                if (!projectTopicsFilterOptions.includes(pto) && pto !== '') {
                    projectTopicsFilterOptions.push(pto);

                }
            });
        }
    })

    values[3].forEach((paper) => {
        if (paper.tags.features && paper.tags.features.length > 0) {
            paper.tags.features.forEach((pf) => {
                if (!paperFeaturesFilterOptions.includes(pf) && pf !== '') {
                    paperFeaturesFilterOptions.push(pf);
                }
            });
        }

        if (paper.tags.topics && paper.tags.topics.length > 0) {
            paper.tags.topics.forEach((pat) => {
                if (!paperTopicsFilterOptions.includes(pat) && pat !== '') {
                    paperTopicsFilterOptions.push(pat);
                }
            });
        }
    })

    return {
        licenseFilterOptions: licenseFilterOptions,
        sampleFilterOptions: sampleFilterOptions,
        datasetFeaturesFilterOptions: datasetFeaturesFilterOptions,
        publisherFilterOptions: publisherFilterOptions,
        ageBandFilterOptions: ageBandFilterOptions,
        geographicCoverageFilterOptions: geographicCoverageFilterOptions,
        
        toolCategoriesFilterOptions: toolCategoriesFilterOptions,
        programmingLanguageFilterOptions: programmingLanguageFilterOptions,
        featuresFilterOptions: featuresFilterOptions,
        toolTopicsFilterOptions: toolTopicsFilterOptions,
        
        projectCategoriesFilterOptions: projectCategoriesFilterOptions,
        projectFeaturesFilterOptions: projectFeaturesFilterOptions,
        projectTopicsFilterOptions: projectTopicsFilterOptions,
        
        paperFeaturesFilterOptions: paperFeaturesFilterOptions,
        paperTopicsFilterOptions: paperTopicsFilterOptions
    };
}

module.exports = router;