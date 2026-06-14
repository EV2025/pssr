(function(api) {

  api.sectionConstructor['deepora-upsell'] = api.Section.extend({
      attachEvents: function() {},
      isContextuallyActive: function() {
          return true;
      }
  });

  const deepora_section_lists = ['Main', 'Clients', 'Logo'];
  deepora_section_lists.forEach(deepora_homepage_scroll);

  function deepora_homepage_scroll(item) {
      item = item.replace(/-/g, '_');
      wp.customize.section('deepora_' + item + '_section', function(section) {
          section.expanded.bind(function(isExpanding) {
              wp.customize.previewer.send(item, { expanded: isExpanding });
          });
      });
  }
})(wp.customize);