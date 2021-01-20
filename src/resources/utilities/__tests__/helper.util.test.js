import helperUtil from '../helper.util';

describe('Helper Utility functions', () => {
  test('Censorword function email test, 1@1.co.uk > *@1*****k', () => {
    let testEmail = '1@1.co.uk';
    let expectedEmail = '*@1*****k';
    let resultEmail = helperUtil.censorEmail(testEmail);
    expect(resultEmail).toEqual(expectedEmail);
  });

  test('Censorword function email test, 12@1.co.uk > 1*@1*****k', () => {
    let testEmail = '12@1.co.uk';
    let expectedEmail = '1*@1*****k';
    let resultEmail = helperUtil.censorEmail(testEmail);
    expect(resultEmail).toEqual(expectedEmail);
  });

  test('Censorword function email test, jamie@1234.co.uk > j***e@1********k', () => {
    let testEmail = 'jamie@1234.co.uk';
    let expectedEmail = 'j***e@1********k';
    let resultEmail = helperUtil.censorEmail(testEmail);
    expect(resultEmail).toEqual(expectedEmail);
  });
});