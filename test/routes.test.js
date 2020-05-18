const request = require("supertest");
const testURL  = request( process.env.URL || 'http://localhost:3001');


describe("Wake up API", () => {
  test("Check the api is alive", async () => {
    const response = await testURL.get("/api/dead");
    expect(response.statusCode).toBe(404);
  });
});


describe("Search API", () => {
  test("Search without any parameters should return at least one result", async () => {
    const response = await testURL.get("/api/v1/search");
    expect(response.statusCode).toBe(200);
    let payload = JSON.parse(response.text);

    expect(payload).toHaveProperty('success');
    expect(payload).toHaveProperty('data');
    expect(payload['data'].length).toBeGreaterThanOrEqual(1);
    expect(payload).toHaveProperty('summary');

  });

  ['homebrew','cancer', 'disparity'].forEach(function(searchString) {

    test(`Search for string '${searchString}', first result should contain name or description '${searchString}'`, async () => {
        const response = await testURL.get('/api/v1/search?search='+searchString);
        expect(response.statusCode).toBe(200);
       	let payload = JSON.parse(response.text);
           
        expect(payload).toHaveProperty('success');
        expect(payload).toHaveProperty('data');
        expect(payload['data'].length).toBeGreaterThanOrEqual(1);
        expect(payload).toHaveProperty('summary');


        expect(payload['data'][0]).toHaveProperty('name');
        expect(payload['data'][0]).toHaveProperty('description');
        expect(payload['data'][0]).toHaveProperty('tags');

        let name = payload['data'][0]['name'].toLowerCase() || '';
        let description = payload['data'][0]['description'].toLowerCase() || '';
        let tags = payload['data'][0]['tags'].join().toLowerCase() || '';
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
        expect(payload).toHaveProperty('data');
        expect(payload['data'].length).toBe(0);
        expect(payload).toHaveProperty('summary');

    });

  });


  test("Search for string 'cancer' limit results to 3, 3 or less results should be returned", async () => {
    let searchString = "cancer";
    let maxResults = 3;

    const response = await testURL.get('/api/v1/search?search='+searchString+'&maxResults='+maxResults);
    expect(response.statusCode).toBe(200);
    let payload = JSON.parse(response.text);
        
    expect(payload).toHaveProperty('success');
    expect(payload).toHaveProperty('data');
    expect(payload['data'].length).toBeLessThanOrEqual(maxResults);
    expect(payload).toHaveProperty('summary');
  });

});
