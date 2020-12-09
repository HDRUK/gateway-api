const request = require("supertest");
const testURL  = request( process.env.URL || 'https://api.latest.healthdatagateway.org/');


describe("Wake up API", () => {
  test("Check the api is alive", async () => {
    jest.setTimeout(30000);
    const response = await testURL.get("/api/dead");
    expect(response.statusCode).toBe(404);
  });
}, 30000);


describe("Search API", () => {
  test("Search without any parameters should return at least one result", async () => {
    const response = await testURL.get("/api/v1/search");
    expect(response.statusCode).toBe(200);
    let payload = JSON.parse(response.text);

    expect(payload).toHaveProperty('success');
    expect(payload).toHaveProperty('datasetResults');
    expect(payload['datasetResults'].length).toBeGreaterThanOrEqual(1);
    expect(payload).toHaveProperty('summary');

  });

  ['covid','CMMID'].forEach(function(searchString) {

    test(`Search for string '${searchString}', first tool result should contain name or description '${searchString}'`, async () => {
        const response = await testURL.get('/api/v1/search?search='+searchString);
        expect(response.statusCode).toBe(200);
       	let payload = JSON.parse(response.text);
           
        expect(payload).toHaveProperty('success');
        expect(payload).toHaveProperty('toolResults');
        expect(payload['toolResults'].length).toBeGreaterThanOrEqual(1);
        expect(payload).toHaveProperty('summary');


        expect(payload['toolResults'][0]).toHaveProperty('name');
        expect(payload['toolResults'][0]).toHaveProperty('description');
        expect(payload['toolResults'][0]).toHaveProperty('tags');

        let name = payload['toolResults'][0]['name'].toLowerCase() || '';
        let description = payload['toolResults'][0]['description'].toLowerCase() || '';
        let tags = payload['toolResults'][0]['tags']['topics'].join().toLowerCase() || '';
        let string = searchString.toLowerCase();

        expect( name.includes(string) || description.includes(string) || tags.includes(string)).toBeTruthy();
        
    });

  });


  //add other things to search for here THAT SHOULD NOT RETURN!!!
  ['crap','zzz'].forEach(function(searchString) {

    test(`Search for string '${searchString}', nothing should be returned`, async () => {
        const response = await testURL.get('/api/v1/search?search='+searchString);
        expect(response.statusCode).toBe(200);
       	let payload = JSON.parse(response.text);
           
        expect(payload).toHaveProperty('success');
        expect(payload).toHaveProperty('toolResults');
        expect(payload['toolResults'].length).toBe(0);
        expect(payload).toHaveProperty('summary');

    });

  });

  ['annual district death daily','cancer','epilepsy'].forEach(function(searchString) {

    test(`Search for string '${searchString}', first dataset result should contain name or description '${searchString}'`, async () => {
        const response = await testURL.get('/api/v1/search?search='+searchString);
        expect(response.statusCode).toBe(200);
       	let payload = JSON.parse(response.text);
        expect(payload).toHaveProperty('success');
        expect(payload).toHaveProperty('datasetResults');
        expect(payload['datasetResults'].length).toBeGreaterThanOrEqual(1);
        expect(payload).toHaveProperty('summary');


        expect(payload['datasetResults'][0]).toHaveProperty('name');
        expect(payload['datasetResults'][0]).toHaveProperty('description');
        //expect(payload['datasetResults'][0]).toHaveProperty('keywords');//cant always be expected

        let name = payload['datasetResults'][0]['name'] || '';
        let description = payload['datasetResults'][0]['description'] || '';
        let keywords = payload['datasetResults'][0]['keywords'] || '';

        let expected = [
            expect.stringMatching(searchString.toLowerCase()),
        ];

        expect([name.toLowerCase(), description.toLowerCase(), keywords.toLowerCase()]).toEqual(
            expect.arrayContaining(expected),
        );
    });

  });


  test("Search for string 'cancer' dataset limit results to 40, 40 or less results should be returned", async () => {
    let searchString = "cancer";
    let maxResults = 40;

    const response = await testURL.get('/api/v1/search?search='+searchString);
    expect(response.statusCode).toBe(200);
    let payload = JSON.parse(response.text);
        
    expect(payload).toHaveProperty('success');
    expect(payload).toHaveProperty('datasetResults');
    expect(payload['datasetResults'].length).toBeLessThanOrEqual(maxResults);
    expect(payload).toHaveProperty('summary');
  });

});
