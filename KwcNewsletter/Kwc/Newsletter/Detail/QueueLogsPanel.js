Ext2.ns('Kwc.Newsletter.Detail');
Kwc.Newsletter.Detail.QueueLogsPanel = Ext2.extend(Kwf.Auto.GridPanel, {
    initComponent: function() {
        this.gridConfig = { tbar: [trlKwf('Attention: The data will be deleted 1 year after sending!')] };

        Kwc.Newsletter.Detail.QueueLogsPanel.superclass.initComponent.call(this);
    }
});
Ext2.reg('kwc.newsletter.detail.queueLogs', Kwc.Newsletter.Detail.QueueLogsPanel);
