Ext2.ns('Kwc.Newsletter.Detail');
Kwc.Newsletter.Detail.RunsPanel = Ext2.extend(Kwf.Binding.AbstractPanel, {
    initComponent: function() {
        this.form = new Kwf.Auto.FormPanel({
            controllerUrl: this.formControllerUrl,
            region: 'center'
        });
        this.grid = new Kwf.Auto.GridPanel({
            controllerUrl: this.controllerUrl,
            region: 'west',
            width: 400,
            split: true,
            bindings: [this.form]
        });

        this.layout = 'border';
        this.items = [this.grid, this.form];

        Kwc.Newsletter.Detail.RunsPanel.superclass.initComponent.call(this);
    },

    applyBaseParams : function(baseParams) {
        Kwc.Newsletter.Detail.RunsPanel.superclass.applyBaseParams.call(this, baseParams);
        Ext2.apply(this.baseParams, {
            newsletterId: this.baseParams.componentId.substr(this.baseParams.componentId.lastIndexOf('_')+1)
        });
        this.form.applyBaseParams(this.baseParams);
        this.grid.applyBaseParams(this.baseParams);
    },

    load: function() {
        this.grid.load();
    }
});
Ext2.reg('kwc.newsletter.runs', Kwc.Newsletter.Detail.RunsPanel);
