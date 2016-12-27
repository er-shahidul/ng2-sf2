import { Sf2Page } from './app.po';

describe('sf2 App', function() {
  let page: Sf2Page;

  beforeEach(() => {
    page = new Sf2Page();
  });

  it('should display message saying app works', () => {
    page.navigateTo();
    expect(page.getParagraphText()).toEqual('app works!');
  });
});
